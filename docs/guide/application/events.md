# Integration Events

An integration event is a message that indicates an intention to _notify_ other bounded contexts of a change that has
occurred. It is a _notification_ that something has happened, and other bounded contexts may need to react to it. For
example, "an order has been paid", "a customer has been created", "an event has been cancelled".

Integration event messages define the data contract for the information that is being passed to other bounded
contexts. They are published to an _event bus_, which is the exit point from the application layer of the bounded
context.

Additionally, integration events from other bounded contexts are _received_ to by the bounded context. The application
layer's event bus is the entry point of this message into the bounded context.

## Direction of Communication

Integration events are bi-directional. They are both _published_ by a bounded context, and _subscribed_ to by other
bounded contexts. This means we can refer to them in terms of their direction - specifically:

- **Outbound** integration events, are those _published_ by a bounded context.
- **Inbound** integration events, are those a bounded context _subscribes to_ and therefore _receives_.

### Shared Data Contract

Integration events define the data contract for the information exchange. When a bounded context publishes an event,
the expectation is that any other bounded context that is interested in that event will receive exactly the same
information. What the receiving bounded context does with that information, and how much of the information it uses, is
up to it.

This means that the data contract for an integration event is _shared_ between the bounded contexts. It also means you
should use [symmetrical serialization](#symmetrical-serialization) to ensure that the data contract is consistent.

:::tip
You'll see throughout this chapter that the examples place the integration events in a shared package. This is to
emphasise that the data contract is shared, and that when a bounded context publishes an event, the expectation is that
subscribing bounded contexts will receive exactly the same information.
:::

## Integration Event Messages

Integration event messages are defined by writing a class that implements the `IntegrationEventInterface`. The class
should be named according to event it represents, and should contain properties that represent the data being exchanged.

The integration event interface is light-weight and defines only two methods:

- `uuid()` - the unique identifier for the integration event, which should be the same across your entire system. I.e.
  the UUID that is issued when publishing the event is the same when it is received into all subscribing contexts. This
  allows for deduplication and idempotent processing, as well as tracking (including debugging) the propagation of the
  event through the system.
- `occurredAt()` - the date and time that the event occurred. This is useful to ensure that events are processed in the
  correct order - as well as for tracking and debugging.

For example:

```php
namespace App\Modules\EventManagement\Shared\IntegrationEvents;

use App\Modules\EventManagement\Shared\Enums\CancellationReasonEnum;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;
use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;

final readonly class AttendeeTicketWasCancelled implements IntegrationEventInterface
{
    public function __construct(
        public Uuid $uuid,
        public \DateTimeImmutable $occurredAt,
        public IdentifierInterface $eventId,
        public IdentifierInterface $attendeeId,
        public IdentifierInterface $ticketId,
        public CancellationReasonEnum $reason,
    ) {
    }

    public function uuid(): Uuid
    {
        return $this->uuid;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
```

## Event Bus

The event bus is the mechanism by which integration events are published and received. It is a _message broker_ that
allows bounded contexts to publish events and to subscribe to events.

In DDD Modules, we provide an event bus _abstraction_. This allows you to clearly define in a bounded context how it
publishes outbound integration events, and what happens when it receives inbound events from other contexts. However,
the abstraction leaves you free to use whatever event bus infrastructure component you like - for example,
AWS EventBridge, Google Cloud Pub/Sub, etc.

:::info
Technically you could skip using our EventBus abstraction - and that's fine if you do. However, the advantage of our
abstraction is it enforces clear boundaries, while also allowing consistency between different bounded contexts.

For example, a developer switching to a bounded context they haven't worked on before can easily find what happens when
the bounded context receives an event by looking at the abstraction which they are already familiar with.
:::

Start by defining an interface, which is the interface we expose on our bounded context's
[application interface.](../concepts/encapsulation#application-interface)

```php
namespace App\Modules\EventManagement\BoundedContext\Application\IntegrationEvents;

use CloudCreativity\Modules\EventBus\EventBusInterface;

interface EventManagementEventBusInterface extends EventBusInterface
{
}
```

And then a concrete implementation:

```php
namespace App\Modules\EventManagement\BoundedContext\Application\IntegrationEvents;

use CloudCreativity\Modules\EventBus\EventBus;

final class EventManagementQueryBus extends EventBus implements
    EventManagementEventBusInterface
{
}
```

### Creating an Event Bus

Our event bus implementation is composed of two things:

- **[Publisher](#publisher)** - defines how integration events leave the bounded context; and
- **[Notifier](#notifier)** - defines how the bounded context reacts to integration events from other contexts.

:::tip
You do not need to provide both. For example, if your bounded context only publishes events and does not subscribe to
any, you only need to provide a publisher. And vice versa - if the context only subscribes to events and does not
publish any, you only need to provide a notifier.
:::

Creating an event bus is a matter of composing these two things together. For example:

```php
namespace App\Modules\EventManagement\BoundedContext\Application;

use App\Modules\EventManagement\BoundedContext\Application\IntegrationEvents\{
    EventManagementEventBus,
    EventManagementEventBusInterface,
};
use CloudCreativity\Modules\EventBus\{
    Outbound\PublisherInterface,
    Inbound\NotifierInterface,
};

final class EventManagementApplication implements EventManagementEventBusInterface
{
    // ...other methods

    public function getEventBus(): EventManagementQueryBusInterface
    {
        return new EventManagementEventBus(
            publisher: $this->getPublisher(),
            notifier: $this->getNotifier(),
        );
    }

    private function getPublisher(): PublisherInterface
    {
        // ...create a publisher - examples below
    }

    private function getNotifier(): NotifierInterface
    {
        // ...create a notifier - examples below
    }
}
```

### Publishing Events

Publishing integration events should be implemented as a _side-effect_ of a domain event. To publish the integration
event, you will need an application layer listener. This will combine listening for the domain event with publishing
the integration event to the event bus (which is an application layer component).

For example:

```php
namespace App\Modules\EventManagement\BoundedContext\Application\Listeners;

use App\Modules\EventManagement\BoundedContext\Application\IntegrationEvents;
use App\Modules\EventManagement\BoundedContext\Domain\Events\AttendeeTicketWasCancelled;

final readonly class PublishAttendeeTicketWasCancelled
{
    public function __construct(
        private UuidFactoryInterface $uuidFactory,
        private IntegrationEvents\EventManagementEventBusInterface $eventBus,
    ) {
    }

    public function handle(AttendeeTicketWasCancelled $event): void
    {
        $this->eventBus->publish(
            new IntegrationEvents\AttendeeTicketWasCancelled(
                uuid: $this->uuidFactory->uuid4(),
                occurredAt: $event->occurredAt,
                eventId: $event->eventId,
                attendeeId: $event->attendeeId,
                ticketId: $event->ticketId,
                reason: $event->reason,
            ),
        );
    }
}
```

Remember this is just an example. Here the domain event and the integration event have the same name. That does not
always need to be the case in your domain.

:::tip
You can improve the example publishing of outbound integration events by domain event listeners using an
[Outbox pattern.](../infrastructure/outbox-inbox)
:::

### Receiving Events

Integration events published by other bounded contexts will arrive in your presentation and delivery layer. For example,
a controller for an endpoint that Google Cloud Pub/Sub pushes events to.

The implementation pattern here is to deserialize the incoming event data, converting it to the defined integration
event message. Then this is pushed into your bounded context via its event bus interface - i.e. the entry point for
the bounded context.

Here is an example controller from a Laravel application to demonstrate the pattern:

```php
namespace App\Http\Controllers\Api\PubSub;

use App\Modules\EventManagement\BoundedContext\Application\{
    IntegrationEvents\EventManagementEventBusInterface,
};
use App\Modules\EventManagement\Shared\IntegrationEvents\{
    Serializers\JsonSerializerInterface,
};

class InboundEventController extends Controller
{
    public function __invoke(
        Request $request,
        EventManagementEventBusInterface $eventBus,
        JsonSerializerInterface $serializer,
    ) {
        $validated = $request->validate([
            // ... validation rules
        ]);

        // see the section on serialization patterns
        /** @var IntegrationEventInterface $event */
        $event = $serializer->deserialize($validated['data']);

        $eventBus->notify($event);

        return response()->noContent();
    }
}
```

This is just an _illustrative_ example. It works for a microservice that only has one bounded context - because there
will therefore only be a single bounded context that needs to be notified of the inbound event.

In a modular monolith or a microservice with multiple bounded contexts, you will need to route the inbound event to the
correct bounded context. Or notify all bounded contexts of the inbound event and leave it up to each to decide if they
need to react to it.

:::tip
You can improve the example processing of inbound integration events by your presentation and delivery layer by using
an [Inbox pattern.](../infrastructure/outbox-inbox)
:::

## Publisher

A publisher defines how your bounded context sends integration events _outbound_. You can create a publisher instance
and configure it for your bounded context - then inject it into the event bus implementation, as shown above.

A publisher is composed of one-to-many handlers, with optional middleware. For example:

```php
namespace App\Modules\EventManagement\BoundedContext\Application;

use App\Modules\EventManagement\BoundedContext\Application\IntegrationEvents\{
    EventManagementEventBus,
    EventManagementEventBusInterface,
    Outbound\AttendeeTicketWasCancelledHandler
};
use App\Modules\EventManagement\Shared\IntegrationEvents\{
    AttendeeTicketWasCancelled,
    Serializers\JsonSerializer,
};
use CloudCreativity\Modules\EventBus\{
    IntegrationEventHandlerContainer,
    Middleware\LogOutboundEvent,
    Outbound\Publisher,
};
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;

final class EventManagementApplication implements EventManagementEventBusInterface
{
    // ...other methods

    public function getEventBus(): EventManagementQueryBusInterface
    {
        return new EventManagementEventBus(
            publisher: $this->getPublisher(),
        );
    }

    private function getPublisher(): Publisher
    {
        $publisher = new Publisher(
            handlers: $handlers = new IntegrationEventHandlerContainer(),
            middleware: $middleware = new PipeContainer(),
        );

        /** Bind events to handlers. */
        $handlers->bind(
            AttendeeTicketWasCancelled::class,
            fn() => new AttendeeTicketWasCancelledHandler(
                $this->dependencies->getSecureTopicFactory(),
                new JsonSerializer(),
            ),
        );

        /** Bind middleware factories */
        $middleware->bind(
            LogOutboundEvent::class,
            fn () => new LogOutboundEvent(
                $this->dependencies->getLogger(),
            ),
        );

        /** Attach middleware that runs for all events */
        $bus->through([
            LogOutboundEvent::class,
        ]);

        return $publisher;
    }
}
```

The above example shows the handler for the `AttendeeTicketWasCancelled` event as a class. Handlers can be either
classes or closures, as described below.

:::info
Again, the example above is _illustrative_ in showing a specific handler for a specific event. You may find in your
implementation that the logic for publishing events is more generic, and can be reused across multiple events.
:::

### Publishers as Classes

If you want to use a specific class to handle publishing an event or events, a factory for that class must be bound
via the `bind()` method - as shown above. Your class should have a `publish()` method that implements the publishing
logic.

For example:

```php
namespace App\Modules\EventManagement\BoundedContext\Application\IntegrationEvents\Outbound;

use App\Modules\EventManagement\Shared\IntegrationEvents\{
    AttendeeTicketWasCancelled,
    Serializers\JsonSerializerInterface,
}
use App\Modules\EventManagement\Infrastructure\GooglePubSub\SecureTopicFactoryInterface;

final readonly class AttendeeTicketWasCancelledHandler
{
    public function __construct(
        private SecureTopicFactoryInterface $factory,
        private JsonSerializerInterface $serializer
    ) {
    }

    public function publish(AttendeeTicketWasCancelled $event): void
    {
        $topic = $this->factory->make($event::class);

        $this->topic->send([
            'data' => $this->serializer->serialize($event),
        ]);
    }
}
```

:::tip
Again, this is _illustrative_. You might find you can write one class-based handler that can be re-used for multiple
events.
:::

### Publishers as Closures

If you prefer, you can use a closure for the publishing logic. This is useful where the logic is as simple as handing
off to other dependencies e.g. infrastructure components. Closure-based publishers are bound via the `register()`
method.

For example, the class-based publisher above could be implemented as follows:

```php
$publisher = new Publisher(
    handlers: $handlers = new IntegrationEventHandlerContainer(),
);

$handlers->register(
    AttendeeTicketWasCancelled::class,
    function (AttendeeTicketWasCancelled $event): void {
        $serializer = $this->dependencies->getEventJsonSerializer();
        $topic = $this->dependencies->getSecureTopicFactory()->make($event::class);
        $topic->send(['data' => $this->serializer->serialize($event)]);
    },
);
```

:::warning
When using closure-based publishers, you loose the ability to unit test the publishing logic. Instead, you would need
to _integration_ test it. I.e. your test would need to use the event bus implementation that is created by your
bounded context's application layer.
:::

## Notifier

A notifier defines what your bounded context does when it receives an _inbound_ integration event from another context.
You can create a notifier instance and configure it for your bounded context - then inject it into the event bus
implementation, as shown earlier.

A notifier is composed of one-to-many handlers, with optional middleware. For example:

```php
namespace App\Modules\EventManagement\BoundedContext\Application;

use App\Modules\EventManagement\BoundedContext\Application\IntegrationEvents\{
    EventManagementEventBus,
    EventManagementEventBusInterface,
    Inbound\OrderWasFulfilledHandler
};
use App\Modules\Ordering\Shared\IntegrationEvents\OrderWasFulfilled;
use CloudCreativity\Modules\EventBus\{
    IntegrationEventHandlerContainer,
    Inbound\Notifier,
    Middleware\LogInboundEvent,
};
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;

final class EventManagementApplication implements EventManagementEventBusInterface
{
    // ...other methods

    public function getEventBus(): EventManagementQueryBusInterface
    {
        return new EventManagementEventBus(
            notifier: $this->getNotifier(),
        );
    }

    private function getNotifier(): Notifier
    {
        $notifier = new Notifier(
            handlers: $handlers = new IntegrationEventHandlerContainer(),
            middleware: $middleware = new PipeContainer(),
        );

        /** Bind events to handlers. */
        $handlers->bind(
            OrderWasFulfilled::class,
            fn() => new OrderWasFulfilledHandler(
                $this->getCommandBus(),
            ),
        );

        /** Bind middleware factories */
        $middleware->bind(
            LogInboundEvent::class,
            fn () => new LogInboundEvent(
                $this->dependencies->getLogger(),
            ),
        );

        /** Attach middleware that runs for all events */
        $bus->through([
            LogInboundEvent::class,
        ]);

        return $notifier;
    }
}
```

The above example shows the handler for the `OrderWasFulfilled` event as a class. Handlers can be either
classes or closures, as described below.

:::info
Unlike publishing logic, which is likely to be reusable across multiple events, the logic for handling inbound
integration events is likely to be specific to the event. This is because the _effect_ on the receiving bounded
context is likely to be unique for the scenario the event represents.
:::

### Strategies

Typically a bounded context subscribes to an event from another bounded context because it needs to mutate its state
as a result of that event occurring. There are several strategies for handling inbound integration events. Choose the
one that is best for your particular use-case. The suggested strategies are:

- **Dispatching or queuing command messages** - the inbound event triggers a command that is dispatched via the command
  bus, either synchronously or asynchronously. One of the advantages of this approach is that it allows you to reuse
  the command for other delivery mechanisms. For example, if you wanted to trigger the same state mutation via a console
  command or HTTP controller.
- **Queuing internal work** - the inbound event results in a job being pushed onto a queue for asynchronous processing.
  This is useful where the work is _internal_ to the bounded context, and does not need to be exposed in your
  application layer as a command message. See the [asynchronous processing chapter](../infrastructure/queues) for the
  distinction between internal and external processing.
- **Dispatching domain events** - the inbound event is mapped to a domain event that has _meaning_ within the receiving
  bounded context. This allows multiple side-effects to be triggered by the domain event. This strategy is
  advantageous if you dispatch the same domain event in other places within your domain code, e.g. from an
  aggregate. This enables the same side-effects to be consistently triggered each time the domain event occurs,
  regardless of the source.

:::warning
If you are using the domain event strategy, ideally you will need to dispatch it within a unit of work. This ensures
that all side-effects are committed or rolled back together. Our example class-based notifier below shows this approach.
:::

### Notifiers as Classes

If you want to use a specific class to handle an inbound event, it needs to be registered on the handler container
using the `bind()` method - as shown in the example above. Your class should have a `handle()` method that
implements the receiving logic.

For example, a class to dispatch a command could look like this:

```php
namespace App\Modules\EventManagement\BoundedContext\Application\IntegrationEvents\Inbound;

use App\Modules\EventManagement\BoundedContext\Application\Commands\{
    EventManagementCommandBusInterface,
    RecalculateSalesAtEvent\RecalculateSalesAtEventCommand,
};
use App\Modules\Ordering\Shared\IntegrationEvents\OrderWasFulfilled;

final readonly class OrderWasFulfilledHandler
{
    public function __construct(
        private EventManagementCommandBusInterface $bus,
    ) {
    }

    public function handle(OrderWasFulfilled $event): void
    {
        $this->bus->dispatch(new RecalculateSalesAtEventCommand(
            eventId: $event->eventId,
        ));
    }
}
```

If your strategy is to dispatch a domain event, your class might look like this:

```php
namespace App\Modules\EventManagement\BoundedContext\Application\IntegrationEvents\Inbound;

use App\Modules\EventManagement\BoundedContext\Domain\Events\SalesAtEventDidChange;
use App\Modules\Ordering\Shared\IntegrationEvents\OrderWasFulfilled;
use CloudCreativity\Modules\Domain\Events\DispatcherInterface;
use CloudCreativity\Modules\EventBus\Middleware\NotifyInUnitOfWork;
use CloudCreativity\Modules\Toolkit\Messages\DispatchThroughMiddleware;

final readonly class OrderWasFulfilledHandler implements
    DispatchThroughMiddleware
{
    public function __construct(
        private DispatcherInterface $domainEvents,
    ) {
    }

    public function handle(OrderWasFulfilled $event): void
    {
        $this->domainEvents->dispatch(new SalesAtEventDidChange(
            eventId: $event->eventId,
        ));
    }

    public function middleware(): array
    {
        return [
            NotifyInUnitOfWork::class,
        ];
    }
}
```

### Notifiers as Closures

If you prefer, you can use a closure for the receiving logic. This is useful where the logic is as simple as handing
off to other dependencies e.g. dispatching a command. Closure-based notifiers are bound via the `register()` method.

For example, the dispatching of a command could be implemented as follows:

```php
$notifier = new Notifier(
    handlers: $handlers = new IntegrationEventHandlerContainer(),
);

$handlers->register(
    OrderWasFulfilled::class,
    function (AttendeeTicketWasCancelled $event): void {
        $this->getCommandBus()->dispatch(
            new RecalculateSalesAtEventCommand(
                eventId: $event->eventId,
            ),
        );
    },
);
```

## Symmetrical Serialization

As an integration event's data contract does not change between being _published_ and _received_, we should use a
serialization pattern that is _symmetrical_.

This can be implemented via a serializer. It guarantees that if you use the same serializer for both serialization and
deserialization, the result will always be an identical integration event message.

This should be expressed via an interface - for example, a JSON serializer:

```php
namespace App\Modules\EventManagement\Shared\IntegrationEvents\Serializers;

interface JsonSerializerInterface
{
    /**
     * @param IntegrationEventInterface $event
     * @return array<string, mixed>
     */
    public function serialize(IntegrationEventInterface $event): array;

    /**
     * @param array<string, mixed>
     * @return IntegrationEventInterface
     */
    public function deserialize(array $input): IntegrationEventInterface;
}
```

:::tip
This serializer interface and your concrete implementation should be in your shared package. This is so that the same
serializer can be used by both the publishing bounded context and the presentation and delivery layer that processes
inbound events.
:::

## Middleware

Our event bus implementation gives you complete control over how to compose the handling of integration events, via
middleware. Middleware is a powerful way to add cross-cutting concerns to your event handling, such as logging.

As shown in the examples in this chapter, middleware can be added to the publisher or notifier instances that are
injected into the event bus. These will then run for every event. To apply middleware to the publisher or notifier,
use the `through()` method - as shown in the earlier examples. Middleware is executed in the order it is added.

Additionally, you can add middleware to individual class-based handlers. To do this, implement the
`DispatchThroughMiddleware` interface. The `middleware()` method should then return an array of middleware to run, in
the order they should be executed. Handler middleware are always executed _after_ the publisher/notifier middleware.

This package provides several useful middleware, which are described below. Additionally, you can write your own
middleware to suit your specific needs.

### Setup and Teardown

Our `SetupBeforeEvent` middleware allows your to run setup work before the event is published or notified, and
optionally teardown work after.

This allows you to set up any state, services or singletons - and guarantee that these are cleaned up, regardless of
whether the notifying or publishing completes or throws an exception.

For example:

```php
use App\Modules\EventManagement\BoundedContext\Domain\Services;
use CloudCreativity\Modules\EventBus\Middleware\SetupBeforeEvent;

$middleware->bind(
    SetupBeforeEvent::class,
    fn () => new SetupBeforeEvent(function (): Closure {
        // setup singletons, dependencies etc here.
        return function (): void {
            // teardown singletons, dependencies etc here.
            // returning a teardown closure is optional.
        };
    }),
);

$bus->through([
    LogInboundEvent::class,
    SetupBeforeEvent::class,
]);
```

Here our setup middleware takes a setup closure as its only constructor argument. This setup closure can optionally
return a closure to do any teardown work. The teardown callback is guaranteed to always be executed - i.e. it will run
even if an exception is thrown.

If you only need to do any teardown work, use the `TeardownAfterEvent` middleware instead. This takes a single teardown
closure as its only constructor argument:

```php
use CloudCreativity\Modules\EventBus\Middleware\TearDownAfterEvent;

$middleware->bind(
    TearDownAfterEvent::class,
    fn () => new TearDownAfterEvent(function (): Closure {
        // teardown here
    }),
);

$bus->through([
    LogInboundEvent::class,
    TearDownAfterEvent::class,
]);
```

### Unit of Work

Ideally notifiers that are not dispatching commands should always be executed in a unit of work.
We cover this in detail in the [Units of Work chapter.](../infrastructure/units-of-work)

:::tip
If your notifier only dispatches a command, then it will not need to be wrapped in a unit of work. This is because the
command itself should use a unit of work.
:::

To notify an event in a unit of work, you will need to use our `NotifyInUnitOfWork` middleware. You should always
implement this as handler middleware - because typically you need it to be the final middleware that runs before a
handler is invoked. It also makes it clear to developers looking at the handler that it is expected to run
in a unit of work. The example `OrderWasFulfilledHandler` above demonstrates this.

An example binding for this middleware is:

```php
use CloudCreativity\Modules\EventBus\Middleware\NotifyInUnitOfWork;

$middleware->bind(
    NotifyInUnitOfWork::class,
    fn () => new NotifyInUnitOfWork($this->getUnitOfWorkManager()),
);
```

:::warning
If you're using a unit of work, you should be combining this with our "unit of work domain event dispatcher".
One really important thing to note is that you **must inject both the middleware and the domain event dispatcher with
exactly the same instance of the unit of work manager.**

I.e. use a singleton instance of the unit of work manager. Plus use the teardown middleware (described above) to dispose
of the singleton instance once the handler has been executed.
:::

### Logging

Use our `LogInboundEvent` or `LogOutboundEvent` middleware to log when an integration event is received or published.
Both take a [PSR Logger](https://php-fig.org/psr/psr-3/).

The only difference between these two middleware is they log a different message that makes it clear whether the
integration event is inbound or outbound. Make sure you use the correct one for the publisher or notifier! The publisher
needs to use `LogOutboundEvent` and the notifier needs to use `LogInboundEvent`.

```php
use CloudCreativity\Modules\EventBus\Middleware\LogInboundEvent;

$middleware->bind(
    LogInboundEvent::class,
    fn () => new LogInboundEvent(
        $this->dependencies->getLogger(),
    ),
);
```

The use of this middleware is identical to that described in the [Commands chapter.](./commands#logging)
See those instructions for more information, such as configuring the log levels.

Additionally, if you need to customise the context that is logged for an integration event then implement the
`ContextProviderInterface` on your integration event message. See the example in the
[Commands chapter.](./commands#logging)

### Writing Middleware

You can write your own middleware to suit your specific needs. Middleware is a simple invokable class, with the
following signature:

```php
namespace App\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\EventBus\Middleware\EventBusMiddlewareInterface;
use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;

final class MyMiddleware implements EventBusMiddlewareInterface
{
    /**
     * Execute the middleware.
     *
     * @param IntegrationEventInterface $event
     * @param Closure(IntegrationEventInterface): void $next
     * @return void
     */
    public function __invoke(
        IntegrationEventInterface $event,
        Closure $next,
    ): void
    {
        // code here executes before the event handler

        $next($command);

        // code here executes after the event handler
    }
}
```

:::tip
If you're writing middleware that is only meant to be used for a specific integration event, do not use the
`EventBusMiddlewareInterface`. Instead, use the same signature but change the event type-hint to the event class your
middleware is designed to be used with.
:::
