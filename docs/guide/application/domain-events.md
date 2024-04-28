# Domain Events

Aggregate roots in our domain layer can emit domain events. This was covered by
the [chapter in the domain layer.](../domain/events)

Domain events are how the domain layer communicates with the application layer. They notify the application layer that
something has happened in the domain. The application layer can then react to these events via event listeners. These
listeners coordinate side effects including interactions with the infrastructure layer via driven ports.

This chapter covers how to implement this coordination in the application layer.

## Event Dispatchers

As a recap, our domain layer uses the _dependency inversion_ principle when emitting domain events. This means the
domain layer defines an event dispatcher interface, but it does not provide the concrete implementation of this
interface.

Instead, the application layer must provide the concrete implementation. This allows the application layer to attach
listeners for domain events, and coordinate side effects via these listeners.

This is what the interface looks like in our domain layer:

```php
namespace App\Modules\EventManagement\Domain\Events;

use CloudCreativity\Modules\Domain\Events\DispatcherInterface;

interface EventDispatcherInterface extends DispatcherInterface
{
}
```

We provide two concrete dispatcher implementations that you can use:

1. **Unit of work aware dispatcher.** This is the preferred implementation. It coordinates dispatching domain events and
   executing listeners with the unit of work manager. [The unit of work chapter](../infrastructure/unit-of-work)
   explains why this is important and how this works.
2. **Deferred event dispatcher.** For use when you cannot use a unit of work in your application layer. This
   implementation attempts to achieve some of the benefits of the unit of work pattern without a full implementation.

:::warning
Wherever possible, use the unit of work approach.
:::

This chapter covers both of these dispatchers.

## Unit of Work Dispatcher

To use this dispatcher, create a concrete implementation of your domain layer's dispatcher interface:

```php
namespace App\Modules\EventManagement\Application\Internal\DomainEvents;

use App\Modules\EventManagement\Domain\Events\DispatcherInterface
use CloudCreativity\Modules\Application\DomainEventDispatching\UnitOfWorkAwareDispatcher;

final class EventDispatcher extends UnitOfWorkAwareDispatcher implements 
    EventDispatcherInterface
{
}
```

### Creating a Dispatcher

To create a unit of work aware dispatcher, you need to provide it with:

1. A unit of work manager. As described in the [unit of work chapter](units-of-work.md), this must be singleton
   instance. I.e. the instance that is provided to your dispatcher must also be the same instance that is provided to
   unit of work middleware.
2. A listener container, that allows you to bind the factories for listeners that the dispatcher will need to
   instantiate.
3. Optionally, middleware factories for any middleware your dispatcher uses.

For example:

```php
namespace App\Modules\EventManagement\Application\Internal\DomainEvents;

use App\Modules\EventManagement\Domain\Events\{
    EventDispatcherInterface,
    AttendeeTicketWasCancelled,
};
use App\Modules\EventManagement\Application\Ports\Driven\DependencyInjection\ExternalDependenciesInterface;
use CloudCreativity\Modules\Application\DomainEventDispatching\ListenerContainer;
use CloudCreativity\Modules\Application\DomainEventDispatching\Middleware\LogDomainEventDispatch;
use CloudCreativity\Modules\Application\UnitOfWork\UnitOfWorkManagerInterface;
use CloudCreativity\Modules\Domain\Events\DomainEventInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;

final readonly class EventDispatcherProvider 
{
    /**
     * @var array<class-string<DomainEventInterface>, list<class-string>>  
     */
    private array $subscriptions = [
        AttendeeTicketWasCancelled::class => [
            Listeners\UpdateTicketSalesReport::class,
            Listeners\QueueTicketCancellationEmail::class,
        ],
        // ...other events
    ];

    public function __construct(
        private ExternalDependenciesInterface $dependencies,
    ) {
    }
    
    public function getEventDispatcher(UnitOfWorkManagerInterface $unitOfWorkManager): EventDispatcherInterface
    {
        $dispatcher = new EventDispatcher(
            unitOfWorkManager: $unitOfWorkManager,
            listeners: $listeners = new ListenerContainer(),
            middleware: $middleware = new PipeContainer(),
        );
        
        /** Bind listener factories */
        $listeners->bind(
            Listeners\UpdateTicketSalesReport::class,
            fn () => new Listeners\UpdateTicketSalesReport(
                $this->dependencies->getTicketSalesReportRepository(),
            ),
        );
        
        $listeners->bind(
            Listeners\QueueTicketCancellationEmail::class,
            fn () => new Listeners\QueueTicketCancellationEmail(
                $this->dependencies->getMailer(),
            ),
        );
        
        /** Subscribe listeners to events */
        foreach ($this->subscriptions as $event => $listeners) {
            $dispatcher->listen($event, $listeners);
        }
        
        /** Bind middleware factories */
        $middleware->bind(
            LogDomainEventDispatch::class,
            fn () => new LogDomainEventDispatch(
                $this->dependencies->getLogger(),
            ),
        );
        
        /** Attach middleware for all events */
        $dispatcher->through([
            LogDomainEventDispatch::class,
        ]);
        
        return $dispatcher;
    }
}
```

### Bootstrapping

We've now got everything we need to use the unit of work aware dispatcher in our application layer. There our however a
few things we need to do to ensure it is correctly bootstrapped.

For example, when creating a command bus there's a few things we'll need to do:

1. Ensure we have a singleton instance of the unit of work manager.
2. Inject this instance into the domain event dispatcher.
3. Ensure that our domain layer can access this dispatcher as a domain service.
4. Ensure our command handlers are wrapped in a unit of work, by injecting the manager into the unit of work command
   middleware.
5. Once a command has been dispatched, reliably tear down the unit of work.

Although this sounds like a lot of work, we provide the tools to make this easy. Here's an example that does all of the
above:

```php
namespace App\Modules\EventManagement\Application\Adapters\CommandBus;

use App\Modules\EventManagement\Application\UsesCases\Commands\{
    CancelAttendeeTicket\CancelAttendeeTicketCommand,
    CancelAttendeeTicket\CancelAttendeeTicketHandler,
    CancelAttendeeTicket\CancelAttendeeTicketHandlerInterface,
};
use App\Modules\EventManagement\Application\Ports\Driving\CommandBus\CommandBusInterface;
use App\Modules\EventManagement\Application\Ports\Driven\DependencyInjection\ExternalDependenciesInterface;
use App\Modules\EventManagement\Application\Internal\DomainEvents\EventDispatcher;
use App\Modules\EventManagement\Application\Internal\DomainEvents\EventDispatcherProvider;
use App\Modules\EventManagement\Domain\Services as DomainServices;
use CloudCreativity\Modules\Application\Bus\CommandHandlerContainer;
use CloudCreativity\Modules\Application\Bus\Middleware\ExecuteInUnitOfWork;
use CloudCreativity\Modules\Application\Bus\Middleware\SetupBeforeDispatch;
use CloudCreativity\Modules\Application\UnitOfWork\UnitOfWorkManager;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;

final class CommandBusAdapterProvider
{
    /**
     * @var UnitOfWorkManager|null 
     */
    private ?UnitOfWorkManager $unitOfWorkManager = null;
    
    /**
     * @var EventDispatcher|null 
     */
    private ?EventDispatcher $eventDispatcher = null;

    public function __construct(
        private readonly ExternalDependenciesInterface $dependencies,
        private readonly EventDispatcherProvider $eventDispatcherProvider,
    ) {
    }

    public function getCommandBus(): CommandBusInterface
    {
        $bus = new CommandBusAdapter(
            handlers: $handlers = new CommandHandlerContainer(),
            middleware: $middleware = new PipeContainer(),
        );

        // ...handler bindings.
        
        $middleware->bind(
            SetupBeforeDispatch::class,
            fn () => new SetupBeforeDispatch(function (): Closure {
                $this->setUp();
                return function (): void {
                    $this->tearDown();
                };
            }),
        );
        
        $middleware->bind(
            ExecuteInUnitOfWork::class,
            fn () => new ExecuteInUnitOfWork($this->unitOfWorkManager),
        );
        
        $bus->through([
            SetupBeforeDispatch::class,
        ]);

        return $bus;
    }
    
    /**
     * Set up command handling state.
     * 
     * @return void 
     */
    private function setUp(): void
    {
        $this->unitOfWorkManager = new UnitOfWorkManager(
            $this->dependencies->getUnitOfWork(),
        );
        
        DomainServices::setEvents(function () {
            if ($this->eventDispatcher) {
                return $this->eventDispatcher;
            }
            
            return $this->eventDispatcher = $this->eventDispatcherProvider->getEventDispatcher(
                $this->unitOfWorkManager
            );
        });
    }
    
    /**
     * Tear down command handling state.
     * 
     * @return void 
     */
    private function tearDown(): void
    {
        DomainServices::tearDown();
        $this->eventDispatcher = null;
        $this->unitOfWorkManager = null;
    }
}
```

### Deferred Events

The unit of work aware dispatcher coordinates deferring events - and therefore the execution of listeners - with the
unit of work manager.

By default, domain events are deferred until just before the transaction commits. This ensures that listeners are
executed within the same transaction boundary as the command handling. This is important for ensuring that the domain
remains consistent.

However, there are times when you may need control over the timing for domain events or their listeners. Our
implementation provides the tools for doing this. For example, a domain event can be marked as needing to be executed
immediately, while the timing of listeners can be controlled by indicating whether they should be executed before or
after the commit.

For full details of the implementation and how to control the unit of work timings, refer to
the [Deferring Domain Events section in the unit of work chapter.](./units-of-work#deferring-domain-events)

## Deferred Event Dispatcher

As a reminder, using this dispatcher is not the preferred approach. Wherever possible, use units of work.

We provide this dispatcher for cases where your implementation cannot use a unit of work. This dispatcher attempts to
achieve some of the benefits of the unit of work pattern without a full implementation.

Create a concrete implementation of your domain layer's dispatcher interface:

```php
namespace App\Modules\EventManagement\Application\Internal\DomainEvents;

use App\Modules\EventManagement\Domain\Events\DispatcherInterface
use CloudCreativity\Modules\Application\DomainEventDispatching\DeferredDispatcher;

final class EventDispatcher extends DeferredDispatcher implements 
    EventDispatcherInterface
{
}
```

### Creating a Dispatcher

The following example shows how to create this dispatcher:

```php
namespace App\Modules\EventManagement\Application\Internal\DomainEvents;

use App\Modules\EventManagement\Domain\Events\{
    EventDispatcherInterface,
    AttendeeTicketWasCancelled,
};
use App\Modules\EventManagement\Application\Ports\Driven\DependencyInjection\ExternalDependenciesInterface;
use CloudCreativity\Modules\Application\DomainEventDispatching\ListenerContainer;
use CloudCreativity\Modules\Application\DomainEventDispatching\Middleware\LogDomainEventDispatch;
use CloudCreativity\Modules\Domain\Events\DomainEventInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;

final readonly class EventDispatcherProvider 
{
    /**
     * @var array<class-string<DomainEventInterface>, list<class-string>>  
     */
    private array $subscriptions = [
        AttendeeTicketWasCancelled::class => [
            Listeners\UpdateTicketSalesReport::class,
            Listeners\QueueTicketCancellationEmail::class,
        ],
        // ...other events
    ];

    public function __construct(
        private ExternalDependenciesInterface $dependencies,
    ) {
    }
    
    public function getEventDispatcher(): EventDispatcherInterface
    {
        $dispatcher = new EventDispatcher(
            listeners: $listeners = new ListenerContainer(),
            middleware: $middleware = new PipeContainer(),
        );
        
        /** Bind listener factories */
        $listeners->bind(
            Listeners\UpdateTicketSalesReport::class,
            fn () => new Listeners\UpdateTicketSalesReport(
                $this->dependencies->getTicketSalesReportRepository(),
            ),
        );
        
        $listeners->bind(
            Listeners\QueueTicketCancellationEmail::class,
            fn () => new Listeners\QueueTicketCancellationEmail(
                $this->dependencies->getMailer(),
            ),
        );
        
        /** Subscribe listeners to events */
        foreach ($this->subscriptions as $event => $listeners) {
            $dispatcher->listen($event, $listeners);
        }
        
        /** Bind middleware factories */
        $middleware->bind(
            LogDomainEventDispatch::class,
            fn () => new LogDomainEventDispatch(
                $this->dependencies->getLogger(),
            ),
        );
        
        /** Attach middleware for all events */
        $dispatcher->through([
            LogDomainEventDispatch::class,
        ]);
        
        return $dispatcher;
    }
}
```

### Bootstrapping

Bootstrapping is simpler for the deferred dispatcher. The main thing you need to ensure is that you keep a singleton
instance of the dispatcher. Your domain layer will need access to this instance, plus the same instance must be injected
into the middleware that flushes deferred events.

Here's an example:

```php
namespace App\Modules\EventManagement\Application\Adapters\CommandBus;

use App\Modules\EventManagement\Application\UsesCases\Commands\{
    CancelAttendeeTicket\CancelAttendeeTicketCommand,
    CancelAttendeeTicket\CancelAttendeeTicketHandler,
    CancelAttendeeTicket\CancelAttendeeTicketHandlerInterface,
};
use App\Modules\EventManagement\Application\Ports\Driving\CommandBus\CommandBusInterface;
use App\Modules\EventManagement\Application\Ports\Driven\DependencyInjection\ExternalDependenciesInterface;
use App\Modules\EventManagement\Application\Internal\DomainEvents\EventDispatcher;
use App\Modules\EventManagement\Application\Internal\DomainEvents\EventDispatcherProvider;
use App\Modules\EventManagement\Domain\Services as DomainServices;
use CloudCreativity\Modules\Application\Bus\CommandHandlerContainer;
use CloudCreativity\Modules\Application\Bus\Middleware\FlushDeferredEvents;
use CloudCreativity\Modules\Application\Bus\Middleware\SetupBeforeDispatch;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;

final class CommandBusAdapterProvider
{
    /**
     * @var EventDispatcher|null 
     */
    private ?EventDispatcher $eventDispatcher = null;

    public function __construct(
        private readonly ExternalDependenciesInterface $dependencies,
        private readonly EventDispatcherProvider $eventDispatcherProvider,
    ) {
    }

    public function getCommandBus(): CommandBusInterface
    {
        $bus = new CommandBusAdapter(
            handlers: $handlers = new CommandHandlerContainer(),
            middleware: $middleware = new PipeContainer(),
        );

        // ...handler bindings.

        $middleware->bind(
            FlushDeferredEvents::class,
            fn () => new ExecuteInUnitOfWork($this->eventDispatcher),
        );
        
        $middleware->bind(
            SetupBeforeDispatch::class,
            fn () => new SetupBeforeDispatch(function (): Closure {
                $this->setUp();
                return function (): void {
                    $this->tearDown();
                };
            }),
        );
        
        $bus->through([
            SetupBeforeDispatch::class,
        ]);

        return $bus;
    }
    
    /**
     * Set up command handling state.
     * 
     * @return void 
     */
    private function setUp(): void
    {
        $this->eventDispatcher = $this->eventDispatcherProvider
                ->getEventDispatcher();
    
        DomainServices::setEvents(fn () => $this->eventDispatcher);
    }
    
    /**
     * Tear down command handling state.
     * 
     * @return void 
     */
    private function tearDown(): void
    {
        DomainServices::tearDown();
        $this->eventDispatcher = null;
    }
}
```

### Deferred Events

This dispatcher works by not immediately dispatching events the domain layer asks it to emit. Instead, events are
dispatched when the dispatcher is asked to flush events.

This is what the `FlushDeferredEvents` middleware does. If the command result is successful, it will tell the event
dispatcher to flush events. If the result was a failure, it instead tells the dispatcher to forget any deferred events.

Use this middleware as the equivalent of the `ExecuteInUnitOfWork` middleware. I.e. apply it as middleware on the
command handler class, and ensure it is the last middleware to be executed.

### Immediate Events

Sometimes you may have side effects for domain events that need to occur immediately rather than being deferred. This
should not be your default approach - but we recognise that sometimes it is unavoidable.

To trigger those side effects immediately, you need to indicate that the domain event should not be deferred when it is
emitted. Implement the `OccursImmediately` interface on the domain event:

```php
namespace App\Modules\EventManagement\Domain\Events;

use CloudCreativity\Modules\Domain\Events\DomainEventInterface;
use CloudCreativity\Modules\Domain\Events\OccursImmediately;

final readonly class AttendeeTicketCancelled implements
    DomainEventInterface,
    OccursImmediately
{
    // ...
}
```

:::warning
Doing this risks means side effects of the domain event will occur, even if something about the command handling
subsequently fails. For example, if there is an error in your infrastructure layer when persisting state changes. This
can compromise the consistency of your domain state.
:::

## Event Listeners

Event listeners are the application layer's way of reacting to domain events. They coordinate side effects, including
interactions with the infrastructure layer via driven ports.

### Class-Based Listeners

Listeners are simple classes that implement a `handle()` method. This method is given the domain event the listener
subscribes to. Dependencies such as driven ports can be injected via the constructor.

There are several examples in the [Use Cases section of the domain layer chapter.](../domain/events#use-cases) Here's
one such example:

```php
namespace App\Modules\EventManagement\Application\Internal\DomainEvents\Listeners;

use App\Modules\EventManagement\Application\Ports\Driven\Persistence\TicketSalesReportRepositoryInterface;
use App\Modules\EventManagement\Domain\Events\AttendeeTicketWasCancelled;

final readonly class UpdateTicketSalesReport
{
    public function __construct(
        private TicketSalesReportRepositoryInterface $repository,
    ) {
    }

    public function handle(AttendeeTicketWasCancelled $event): void
    {
        $report = $this->repository->findByEventId($event->eventId);

        $report->recalculate();

        $this->repository->update($report);
    }
}
```

:::info
This example illustrates why listeners are in the application layer. Although they may trigger actions on aggregate
roots outside the control of the emitting aggregate root, these domain layer side effects would always need persisting
via a driven port.
:::

Class-based listeners are bound into a listener container that is given to the event dispatcher. This is shown in the
examples above, but as a reminder:

```php
$dispatcher = new EventDispatcher(
    listeners: $listeners = new ListenerContainer(),
);

/** Bind listener factories */
$listeners->bind(
    Listeners\UpdateTicketSalesReport::class,
    fn () => new Listeners\UpdateTicketSalesReport(
        $this->dependencies->getTicketSalesReportRepository(),
    ),
);

/** Then subscribe it to events */
$dispatcher->listen(
    AttendeeTicketWasCancelled::class,
    Listeners\UpdateTicketSalesReport::class,
);
```

### Closure Listeners

Our implementation also allows you to use closures as listeners. This can be useful for simple side effects that do not
require a class. However, we recommend using class-based listeners as they are easier to unit test.

Closure listeners are attached to events via the dispatcher, so are not bound into a listener container.

```php
$dispatcher = new EventDispatcher();

$dispatcher->listen(
    AttendeeTicketWasCancelled::class,
    function (AttendeeTicketWasCancelled $event): void {
        $notifier = $this->dependencies
            ->getNotifiers()
            ->getTicketCancellationNotifier();
        $notifier->notify($event->ticketId);
    },
);
```

## Middleware

Middleware can be attached to the dispatcher to perform actions before and/or after a domain event is emitted. This can
be useful for cross-cutting concerns, such as logging.

To apply middleware to the event dispatcher, you can use the `through()` method - as shown in the examples earlier in
this chapter. Middleware is executed in the order it is added to the dispatcher.

### Logging

Use our `LogDomainEventDispatch` middleware to log when an aggregate root emits an event. This middleware logs the event
name when it is dispatched, and when it has been dispatched.

For example:

```php
use CloudCreativity\Modules\Application\DomainEventDispatching\Middleware\LogDomainEventDispatch;

$dispatcher = new EventDispatcher(
    listeners: $listeners = new ListenerContainer(),
    middleware: $middleware = new PipeContainer(),
);

$middleware->bind(
    LogDomainEventDispatch::class,
    fn () => new LogDomainEventDispatch(
        $this->dependencies->getLogger(),
    ),
);

$dispatcher->through([
    LogDomainEventDispatch::class,
]);
```

This works exactly like the logging middleware described in the [commands chapter.](../application/commands#logging)
You can provide a custom logging level for the before and after dispatch log messages.

However, unlike the command bus implementation this middleware does not log any context. This is so that any concept of
logging does not leak into the domain layer.

### Writing Middleware

You can write your own middleware to suit your specific needs. Middleware is a simple invokable class, with the
following signature:

```php
namespace App\Modules\EventManagement\Application\Internal\DomainEvents\Middleware;

use Closure;
use CloudCreativity\Modules\Application\DomainEventDispatching\Middleware\DomainEventMiddlewareInterface;
use CloudCreativity\Modules\Domain\Events\DomainEventInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

final class MyMiddleware implements DomainEventMiddlewareInterface
{
    /**
     * Execute the middleware.
     *
     * @param DomainEventInterface $event
     * @param Closure(DomainEventInterface): void $next
     * @return void
     */
    public function __invoke(
        DomainEventInterface $event, 
        Closure $next,
    ): ResultInterface
    {
        // code here executes before the event is emitted.

        $next($command);

        // code here executes after it is emitted.
    }
}
```

It's worth noting that here we are wrapping the event being emitted by the domain layer, which is the point at which it
may be deferred by the dispatcher.