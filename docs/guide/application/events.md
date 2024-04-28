# Integration Events

An integration event is a message that indicates an intention to _inform_ other bounded contexts that a change has
occurred. It is a _notification_ that something has happened, and other bounded contexts may need to react to it. For
example, "an order has been paid", "a customer has been created", "an event has been cancelled".

Integration event messages define the data contract for the exchange of information between a publishing bounded context
and any consuming bounded contexts.

## Direction of Communication

Integration events are bidirectional. They are both _published_ by a bounded context, and _consumed_ by other
bounded contexts. This means we can refer to them in terms of their direction - specifically:

- **Inbound** integration events, are those a bounded context _consumes_ via a driving port and an adapter
  implementation within the application layer.
- **Outbound** integration events, are those _published_ by a bounded context. Publishing occurs via a driven port, with
  the infrastructure layer implementing the adapter.

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
namespace VendorName\EventManagement\Shared\IntegrationEvents\V1;

use CloudCreativity\Modules\Application\Messages\IntegrationEventInterface;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;
use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use VendorName\EventManagement\Shared\Enums\CancellationReasonEnum;

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

### Shared Data Contract

Integration event messages define the data contract for the information exchange. When a bounded context publishes an
event, the expectation is that any other bounded context that is interested in that event will receive exactly the same
information. What the receiving bounded context does with that information, and how much of the information it uses, is
up to it.

This means that the data contract for an integration event is _shared_ between the bounded contexts.

:::tip
The above example integration event places the message in a shared package. As the data contract is shared, when a
bounded context publishes an event the expectation is that consuming bounded contexts will receive exactly the same
information.

Therefore, the integration event message must be defined in a shared package that is accessible to both the publishing
and consuming bounded contexts.
:::

### Symmetrical Serialization

As an integration event's data contract does not change between being _published_ and _consumed_, we should use a
serialization pattern that is _symmetrical_.

This can be implemented via a serializer. It guarantees that if you use the same serializer for both serialization and
deserialization, the result will always be an identical integration event message.

This can be expressed via an interface. To illustrate the point, a JSON serializer might look like this:

```php
namespace VendorName\Ordering\Shared\IntegrationEvents\V1\Serializers;

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
serializer can be used by both the publishing bounded context and the presentation and delivery layer that receives
inbound events.
:::

### Versioning

As your integration events are consumed by other bounded contexts, you cannot make breaking changes to the data contract
without updating every single consumer to use the new contract.

In large systems, this can be a significant challenge. To mitigate this, you can version your integration events. This
allows you to introduce breaking changes to the data contract, while still supporting older versions of the event. For
example, our integration events could be in `IntegrationEvents\V1` and `IntegrationEvents\V2` namespaces.

This allows you to introduce a new version of the event, while retaining the event name. Retaining the event name is
important because it is an expression of your domain, using the ubiquitous language of your bounded context. If you do
not version your integration events, you'll be forced to rename the event just to introduce a new data contract. Whereas
the priority should be to keep the language of the domain.

This means that when you introduce a new version of the event, the originating bounded context can publish multiple
versions of the event. Over time you can migrate all consumers to the new version, and then remove the old version.

## Outbound Events

Outbound integration events are _published_ by a bounded context. This is typically in response to a domain event, or
because a command has been executed. The event is published via a driven port, with the infrastructure layer
implementing the adapter.

### Outbound Port

Your application layer should define the driven port:

```php
namespace App\Modules\EventManagement\Application\Ports\Driven\OutboundEventBus;

use CloudCreativity\Modules\Application\Ports\Driven\OutboundEventBus\EventPublisherInterface;

interface OutboundEventBusInterface extends EventPublisherInterface
{
}
```

We provide a concrete implementation of the publisher that you can use as your adapter. As this is an infrastructure
adapter, we have documented it in
the [infrastructure layer's Publishing Events chapter.](../infrastructure/publishing-events)

### Domain Event Listener

Publishing integration events should be implemented as a _side effect_ of a domain event. To publish the integration
event, you will need a domain event listener in your application layer that publishes the event via your driven port.

For example:

```php
namespace App\Modules\EventManagement\Application\Internal\DomainEvents\Listeners;

use App\Modules\EventManagement\Application\Ports\Driven\OutboundEvents\OutboundEventBusInterface;
use App\Modules\EventManagement\Domain\Events\AttendeeTicketWasCancelled;
use CloudCreativity\Modules\Toolkit\Identifiers\UuidFactoryInterface;
use VendorName\EventManagement\Shared\IntegrationEvents\V1 as IntegrationEvents;

final readonly class PublishAttendeeTicketWasCancelled
{
    public function __construct(
        private UuidFactoryInterface $uuidFactory,
        private OutboundEventBusInterface $eventBus,
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

:::tip
You can improve the example publishing of outbound integration events by domain event listeners using an
[Outbox pattern.](../infrastructure/outbox-inbox)
:::

## Inbound Events

Inbound events are consumed by event handlers. They represent a _use case_ in the application layer, defining how the
bounded context reacts to inbound events.

Start by expressing the use case as an interface. This defines that the bounded context receives a specific event:

```php
namespace App\Modules\EventMangement\Application\UseCases\InboundEvents;

use VendorName\Ordering\Shared\IntegrationEvents\V1\OrderWasFulfilled;

interface OrderWasFulfilledHandlerInterface
{
    /**
     * Handle an inbound "order was fulfilled" event.
     * 
     * @param OrderWasFulfilled $event
     * @return void
     */
    public function handle(OrderWasFulfilled $event): void;
}
```

Then you can write the concrete implementation of the use case - i.e. how your use case reacts to the event. There are
several different strategies you can use.

### Strategies

Bounded contexts typically consume events from other contexts because they need to mutate their state as a result of
that event occurring. There are several strategies for handling inbound integration events. Choose the one that is best
for your particular use-case.

- **Dispatching or queuing command messages** - the inbound event triggers a command that is dispatched via the command
  bus, either synchronously or asynchronously. One of the advantages of this approach is that it allows you to reuse
  the command for other delivery mechanisms. For example, if you wanted to trigger the same state mutation via a console
  command or HTTP controller.
- **Dispatching or queuing internal command messages** - this is the same as the previous strategy, but in this case you
  want to do work that is _internal_ to the bounded context, i.e. not exposed as a use case of the domain. This is
  implemented via a separate command dispatcher in the application layer's `Internal` namespace. This topic is covered
  in detail in the [asynchronous processing chapter](./asynchronous-processing).
- **Dispatching domain events** - the inbound event is mapped to a domain event that has _meaning_ within the consuming
  bounded context. This allows multiple side effects to be triggered. This strategy is advantageous if you dispatch the
  same domain event in other places within your domain code, e.g. from an aggregate. This enables the same side-effects
  to be consistently triggered each time the domain event occurs, regardless of the source.

There are example handlers for each of these strategies below.

### Command Strategy

An inbound event handler that dispatches a command that is a use case in your application layer would look like this:

```php
namespace App\Modules\EventManagement\Application\UseCases\InboundEvents;

use App\Modules\EventManagement\Application\Ports\Driving\CommandBus\CommandBusInterface;
use App\Modules\EventManagement\Application\UseCases\Commands\RecalculateSalesAtEvent\RecalculateSalesAtEventCommand;
use VendorName\Ordering\Shared\IntegrationEvents\V1\OrderWasFulfilled;

final readonly class OrderWasFulfilledHandler implements
    OrderWasFulfilledHandlerInterface
{
    public function __construct(
        private CommandBusInterface $bus,
    ) {
    }

    public function handle(OrderWasFulfilled $event): void
    {
        // alternatively we could use `queue()` to process the command asynchronously
        $this->bus->dispatch(new RecalculateSalesAtEventCommand(
            eventId: $event->eventId,
        ));
    }
}
```

:::tip
In this scenario, our event handler does not need to be executed in a unit of work. This is because we expect the
command handler to do this.
:::

### Internal Command Strategy

This is almost identical to the previous example. However, in this case the command is internal to the bounded context.
I.e. it is not intended to be exposed as a use case that the outside world can dispatch.

This means the command message and command bus are in the application layer's internal namespace. Otherwise, the
approach is identical to the previous strategy.

```php
namespace App\Modules\EventManagement\Application\UseCases\InboundEvents;

use App\Modules\EventManagement\Application\Internal\Commands\InternalCommandBusInterface;
use App\Modules\EventManagement\Application\Internal\Commands\RecalculateSalesAtEvent\RecalculateSalesAtEventCommand;
use VendorName\Ordering\Shared\IntegrationEvents\V1\OrderWasFulfilled;

final readonly class OrderWasFulfilledHandler implements
    OrderWasFulfilledHandlerInterface
{
    public function __construct(
        private InternalCommandBusInterface $bus,
    ) {
    }

    public function handle(OrderWasFulfilled $event): void
    {
        // alternatively we could use `queue()` to process the command asynchronously
        $this->bus->dispatch(new RecalculateSalesAtEventCommand(
            eventId: $event->eventId,
        ));
    }
}
```

### Domain Event Strategy

For this strategy, we map the inbound integration event to a domain event. Think of this as converting an event that
does not have meaning in the consuming bounded context to one that does have semantic meaning within the domain.

This is useful if you want to trigger multiple side effects when the event occurs. Or where it maps to a domain event
that is already in use by your domain, e.g. emitted by an aggregate. Reusing the domain event ensures exactly the same
side effects are triggered.

```php
namespace App\Modules\EventManagement\Application\UseCases\InboundEvents;

use App\Modules\EventManagement\Domain\Events\DispatcherInterface;
use App\Modules\EventManagement\Domain\Events\SalesAtEventDidChange;
use CloudCreativity\Modules\Application\InboundEventBus\Middleware\HandleInUnitOfWork;
use CloudCreativity\Modules\Application\Messages\DispatchThroughMiddleware;
use VendorName\Ordering\Shared\IntegrationEvents\V1\OrderWasFulfilled;

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
            HandleInUnitOfWork::class,
        ];
    }
}
```

:::warning
If you are using this strategy and your bounded context uses unit of works, you must dispatch the event in a unit of
work. This ensures that all side effects are committed or rolled back together. The above example does this via
middleware.
:::

## Inbound Event Bus

Inbound integration events are received from the outside world. To consume these events, our bounded context must expose
an inbound event bus as a driving port. Although there is a _generic_ inbound event bus interface, our bounded context
needs to expose its _specific_ inbound event bus.

We do this by defining an interface in our application's driving ports:

```php
namespace App\Modules\EventManagement\Application\Ports\Driving\InboundEventBus;

use CloudCreativity\Modules\Application\Ports\Driving\InboundEvents\EventDispatcherInterface;

interface InboundEventBusInterface extends EventDispatcherInterface
{
}
```

And then our adapter (the concrete implementation of the port) is as follows:

```php
namespace App\Modules\EventManagement\Application\Adapters\InboundEventBus;

use App\Modules\EventManagement\Application\Ports\Driving\InboundEventBus\InboundEventBusInterface;
use CloudCreativity\Modules\Application\InboundEventBus\EventDispatcher;

final class InboundEventBusAdapter extends EventDispatcher implements
    InboundEventBusInterface
{
}
```

### Creating a Bus

The event dispatcher class that your adapter extended (in the above example) allows you to build an inbound event bus
specific to your domain. You do this by:

1. Binding event handler factories into the event dispatcher; and
2. Binding factories for any middleware used by your bounded context; and
3. Optionally, attaching middleware that runs for all inbound events dispatched through the event bus.

Factories must always be lazy, so that the cost of instantiating event handlers or middleware only occurs if the handler
or middleware are actually being used.

For example:

```php
namespace App\Modules\EventManagement\Application\Adapters\InboundEventBus;

use App\Modules\EventManagement\Application\Adapters\CommandBus\CommandBusAdapterProvider;
use App\Modules\EventManagement\Application\Ports\Driving\InboundEventBus\InboundEventBusInterface;
use App\Modules\EventManagement\Application\Ports\Driven\DependencyInjection\ExternalDependenciesInterface;
use CloudCreativity\Modules\Application\InboundEventBus\EventHandlerContainer;
use CloudCreativity\Modules\Application\InboundEventBus\Middleware\HandleInUnitOfWork;
use CloudCreativity\Modules\Application\InboundEventBus\Middleware\LogInboundEvent;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;
use VendorName\Ordering\Shared\IntegrationEvents\V1\OrderWasFulfilled;

final class InboundEventBusAdapterProvider
{
    public function __construct(
        private readonly CommandBusAdapterProvider $commandBusProvider,
        private readonly ExternalDependenciesInterface $dependencies,
    ) {
    }

    public function getEventBus(): InboundEventBusInterface
    {
        $bus = new InboundEventBusAdapter(
            handlers: $handlers = new EventHandlerContainer(),
            middleware: $middleware = new PipeContainer(),
        );

        /** Bind integration events to handler factories */
        $handlers->bind(
            OrderWasFulfilled::class,
            fn(): OrderWasFulfilledHandlerInterface => new OrderWasFulfilledHandler(
                $this->commandBusProvider->getCommandBus(),
            ),
        );

        /** Bind middleware factories */
        $middleware->bind(
            HandleInUnitOfWork::class,
            fn () => new HandleInUnitOfWork($this->dependencies->getUnitOfWorkManager()),
        );

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

        return $bus;
    }
}
```

Inbound events are received by the presentation and delivery layer of your application. For example, a controller that
receives a push message from Google Cloud Pub/Sub. We therefore need to bind the driving port and its adapter into a
service container. For example, in Laravel:

```php
namespace App\Providers;

use App\Modules\EventManagement\Application\{
    Adapters\InboundEventBus\InboundEventBusAdapterProvider,
    Ports\Driving\InboundEvents\InboundEventBusInterface,
};
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class EventManagementServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            InboundEventBusInterface::class,
            static function (Container $app)  {
                $provider = $app->make(InboundEventBusAdapterProvider::class);
                return $provider->getEventBus();
            },
        );
    }
}
```

### Consuming Events

Integration events published by other bounded contexts will arrive in your presentation and delivery layer. For example,
a controller for an endpoint that Google Cloud Pub/Sub pushes events to.

The implementation pattern here is to deserialize the incoming event data, converting it to the defined integration
event message. Then this is pushed into your bounded context via its event bus interface - i.e. the entry point for
the bounded context.

Here is an example controller from a Laravel application to demonstrate the pattern:

```php
namespace App\Http\Controllers\Api\PubSub;

use App\Modules\EventManagement\Application\Ports\Driving\InboundEvents\InboundEventBusInterface;
use VendorName\Ordering\Shared\IntegrationEvents\V1\Serializers\JsonSerializerInterface;

class InboundEventController extends Controller
{
    public function __invoke(
        Request $request,
        InboundEventBusInterface $eventBus,
        JsonSerializerInterface $serializer,
    ) {
        $validated = $request->validate([
            // ... validation rules
        ]);

        // see the section on serialization patterns
        /** @var IntegrationEventInterface $event */
        $event = $serializer->deserialize($validated['data']);

        $eventBus->dispatch($event);

        return response()->noContent();
    }
}
```

This is just an _illustrative_ example. It works for a microservice that only has one bounded context - because there
will therefore only be a single bounded context that consumes the inbound event.

In a modular monolith or a microservice with multiple bounded contexts, you will need to route the inbound event to the
correct bounded context. Or notify all bounded contexts of the inbound event and leave it up to each to decide if they
need to react to it.

:::tip
You can improve the example processing of inbound integration events by your presentation and delivery layer by using
an [Inbox pattern.](../infrastructure/outbox-inbox)
:::

## Inbound Middleware

Our inbound event bus implementation gives you complete control over how to compose the handling of integration events,
via middleware. Middleware is a powerful way to add cross-cutting concerns to your event handling, such as logging.

Middleware can be added either to the inbound event bus (so it runs for every event) or to individual event handlers.

To apply middleware to the inbound event bus, use the `through()` method - as shown in the earlier examples.
Middleware is executed in the order it is added.

Additionally, you can add middleware to individual handler classes. To do this, implement the
`DispatchThroughMiddleware` interface. The `middleware()` method should then return an array of middleware to run, in
the order they should be executed. Handler middleware are always executed _after_ the event bus middleware.

This package provides several useful middleware, which are described below. Additionally, you can write your own
middleware to suit your specific needs.

### Setup and Teardown

Our `SetupBeforeEvent` middleware allows your to run setup work before the event is published or notified, and
optionally teardown work after.

This allows you to set up any state, services or singletons - and guarantee that these are cleaned up, regardless of
whether the notifying or publishing completes or throws an exception.

For example:

```php
use CloudCreativity\Modules\Application\InboundEventBus\Middleware\SetupBeforeEvent;

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
use CloudCreativity\Modules\Application\InboundEventBus\Middleware\TearDownAfterEvent;

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

Ideally consumers that are not dispatching commands should always be executed in a unit of work.
We cover this in detail in the [Units of Work chapter.](units-of-work.md)

:::tip
If your consumer only dispatches a command, then it will not need to be wrapped in a unit of work. This is because the
command itself should use a unit of work.
:::

To consume an event in a unit of work, you will need to use our `HandleInUnitOfWork` middleware. You should always
implement this as handler middleware - because typically you need it to be the final middleware that runs before a
handler is invoked. It also makes it clear to developers looking at the handler that it is expected to run
in a unit of work. The example `OrderWasFulfilledHandler` above demonstrates this.

An example binding for this middleware is:

```php
use CloudCreativity\Modules\Application\InboundEventBus\Middleware\HandleInUnitOfWork;

$middleware->bind(
    HandleInUnitOfWork::class,
    fn () => new HandleInUnitOfWork($this->dependencies->getUnitOfWorkManager()),
);
```

:::warning
If you're using a unit of work, you should be combining this with our "unit of work domain event dispatcher".
One really important thing to note is that you **must inject both the middleware and the domain event dispatcher with
exactly the same instance of the unit of work manager.**

I.e. use a singleton instance of the unit of work manager. Plus use the teardown middleware (described above) to dispose
of the singleton instance once the handler has been executed.
:::

### Flushing Deferred Events

If you are not using a unit of work, you will most likely be using our deferred domain event dispatcher. This is covered
in the [Domain Events chapter.](./domain-events)

When using this dispatcher, you will need to use our `FlushDeferredEvents` middleware. You should always
implement this as handler middleware - because typically you need it to be the final middleware that runs before a
handler is invoked. I.e. this is an equivalent middleware to the unit of work middleware.

An example binding for this middleware is:

```php
use CloudCreativity\Modules\Application\InboundEventBus\Middleware\FlushDeferredEvents;

$middleware->bind(
    FlushDeferredEvents::class,
    fn () => new FlushDeferredEvents(
        $this->eventDispatcher,
    ),
);
```

:::warning
When using this middleware, it is important that you inject it with a singleton instance of the deferred event
dispatcher. This must be the same instance that is exposed to your domain layer as a service.
:::

### Logging

Use our `LogInboundEvent` middleware to log when an integration event is consumed. It takes
a [PSR Logger](https://php-fig.org/psr/psr-3/).

```php
use CloudCreativity\Modules\Application\InboundEventBus\Middleware\LogInboundEvent;

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
namespace App\Modules\EventManagement\Application\Adapters\Middleware;

use Closure;
use CloudCreativity\Modules\Application\InboundEventBus\Middleware\InboundEventMiddlewareInterface;
use CloudCreativity\Modules\Application\Messages\IntegrationEventInterface;

final class MyMiddleware implements InboundEventMiddlewareInterface
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
`InboundEventMiddlewareInterface`. Instead, use the same signature but change the event type-hint to the event class
your middleware is designed to be used with.
:::
