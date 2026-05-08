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

use CloudCreativity\Modules\Contracts\Domain\Events\DomainEventDispatcher as BaseDispatcher;

interface DomainEventDispatcher extends BaseDispatcher
{
}
```

We provide two concrete dispatcher implementations that you can use:

1. **Unit of work aware dispatcher.** This is the preferred implementation. It coordinates dispatching domain events and
   executing listeners with the unit of work manager. [The unit of work chapter](./units-of-work) explains why this is
   important and how this works.
2. **Deferred event dispatcher.** For use when you cannot use a unit of work in your application layer. This
   implementation attempts to achieve some of the benefits of the unit of work pattern without a full implementation.

:::warning
Wherever possible, use the unit of work approach.
:::

This chapter covers both of these dispatchers.

## Unit of Work Dispatcher

To use this dispatcher, create a concrete implementation of your domain layer's dispatcher interface:

```php
namespace App\Modules\EventManagement\Application\Orchestration;

use App\Modules\EventManagement\Domain\Events\DomainEventDispatcher as IDomainEventDispatcher;
use CloudCreativity\Modules\Application\DomainEventDispatching\UnitOfWorkAwareDispatcher;
use CloudCreativity\Modules\Application\DomainEventDispatching\ListenTo;
use CloudCreativity\Modules\Toolkit\Pipeline\Through;

#[Through(LogDomainEventDispatch::class)]
#[ListenTo(SomeDomainEvent::class, FooListener::class)]
#[ListenTo(SomeOtherDomainEvent::class, [BarListener::class, BazListener::class])]
final class DomainEventDispatcher extends UnitOfWorkAwareDispatcher implements
    IDomainEventDispatcher
{
}
```

Notice that middleware is bound to the dispatcher using the `Through` attribute.

Events are mapped to listeners via the `ListenTo` attribute. You can specify a single listener or an array of listeners
for each event.

### Creating a Dispatcher

To create a unit of work aware dispatcher, you need to provide it with a unit of work manager. As described in
the [unit of work chapter](units-of-work.md), this MUST be a singleton instance. I.e. the instance that is provided to
your dispatcher must also be the same instance that is provided to unit of work middleware.

You also need to provide a PSR container, so that the dispatcher can resolve any listeners and middleware.

For example:

```php
use CloudCreativity\Modules\Contracts\Application\UnitOfWork\UnitOfWorkManager;

$dispatcher = new DomainEventDispatcher(
    $container->get(UnitOfWorkManager::class),
    $container,
);
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

As well as implementing your domain layer's dispatcher interface, you also need to implement a deferred dispatcher
interface. Combine these two as an interface in your application layer:

```php
namespace App\Modules\EventManagement\Application\Orchestration;

use App\Modules\EventManagement\Domain\Events\DomainEventDispatcher;
use CloudCreativity\Modules\Contracts\Application\DomainEventDispatching\DeferredDispatcher;

interface DeferredDomainEventDispatcher extends DomainEventDispatcher, DeferredDispatcher
{
}
```

Then create a concrete implementation of your domain layer's dispatcher interface:

```php
namespace App\Modules\EventManagement\Application\Orchestration;

use CloudCreativity\Modules\Application\DomainEventDispatching\DeferredDispatcher;

final class DomainEventDispatcherAdapter extends DeferredDispatcher implements
    DeferredDomainEventDispatcher
{
}
```

### Creating a Dispatcher

To create a deferred dispatcher, you need to provide a PSR container. This allows the dispatcher to resolve any
listeners and middleware.

For example:

```php

$dispatcher = new DomainEventDispatcher($container);
```

The main thing you need to ensure is that your instance of this domain event dispatcher is a singleton. This ensures
that the same instance is used by the domain to dispatch events, plus by middleware to flush the dispatcher at the
correct moment.

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

use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;
use CloudCreativity\Modules\Contracts\Domain\Events\OccursImmediately;

final readonly class AttendeeTicketCancelled implements
    DomainEvent,
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
namespace App\Modules\EventManagement\Application\Orchestration\Listeners;

use App\Modules\EventManagement\Application\Ports\Persistence\TicketSalesReportRepository;
use App\Modules\EventManagement\Domain\Events\AttendeeTicketWasCancelled;

final readonly class UpdateTicketSalesReport
{
    public function __construct(
        private TicketSalesReportRepository $repository,
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

Use the `ListenTo` attribute on the dispatcher class to bind listeners to events.

## Middleware

Middleware can be attached to the dispatcher to perform actions before and/or after a domain event is emitted. This can
be useful for cross-cutting concerns, such as logging.

To apply middleware to the event dispatcher, you can use the `Through` attribute - as shown in the examples earlier in
this chapter. Middleware is executed in the order it is added to the dispatcher.

### Logging

Use our `LogDomainEventDispatch` middleware to log when an aggregate root emits an event. This middleware logs the event
name when it is dispatched, and when it has been dispatched.

This works exactly like the logging middleware described in the [commands chapter.](../application/commands#logging)
You can provide a custom logging level for the before and after dispatch log messages.

However, unlike the command bus implementation this middleware does not log any context. This is so that any concept of
logging does not leak into the domain layer.

### Writing Middleware

You can write your own middleware to suit your specific needs. Middleware is a simple invokable class, with the
following signature:

```php
namespace App\Modules\EventManagement\Application\Orchestration\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Application\DomainEventDispatching\DomainEventMiddleware;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

final class MyMiddleware implements DomainEventMiddleware
{
    /**
     * Execute the middleware.
     *
     * @param DomainEvent $event
     * @param Closure(DomainEvent): void $next
     * @return void
     */
    public function __invoke(
        DomainEvent $event,
        Closure $next,
    ): Result
    {
        // code here executes before the event is emitted.

        $next($command);

        // code here executes after it is emitted.
    }
}
```

It is worth noting that here we are wrapping the event being emitted by the domain layer, which is the point at which it
may be deferred by the dispatcher.
