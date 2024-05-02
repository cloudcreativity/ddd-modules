# Publishing Integration Events

Integration events are covered in detail in the [chapter in the application layer.](../application/events)

That chapter describes how outbound integration events are published via a driven port defined by the application layer.
As this is a driven port, the adapter implementation is in the infrastructure layer. This chapter describes how to
implement an adapter for publishing outbound events.

## Outbound Event Bus

The outbound event bus is a component that sends integration events to external systems. It is a driven port defined by
the application layer and implemented in the infrastructure layer as an adapter.

The following is an example port:

```php
namespace App\Modules\EventManagement\Application\Ports\Driven\OutboundEventBus;

use CloudCreativity\Modules\Contracts\Application\Ports\Driven\OutboundEventBus\EventPublisher;

interface OutboundEventBusInterface extends EventPublisher
{
}
```

:::warning
We strongly advocate for using a [transactional outbox](./outbox) pattern for outbound integration events. I.e. place
the outbound integration event in an outbox, which when processed then publishes the event. See the linked chapter for
details.
:::

We provide several adapter classes for this port. These allow you either to publish events via closures, or by
class-based handlers.

Closures are useful for simple event publishing logic, while class-based publishers are useful for more complex logic or
where you want to use constructor dependency injection for reusable concerns.

## Closure Publishing

If you want to publish via closures, use our `ClosurePublisher` implementation. This is useful where the publishing
logic is simple. This is the case in the following example, where the publisher just needs to hand off the event to a
Google Pub/Sub implementation.

Define an adapter by extending this class:

```php
namespace App\Modules\EventManagement\Infrastructure\OutboundEventBus;

use App\Modules\EventManagement\Application\Ports\Driven\OutboundEventBus\OutboundEventBusInterface;
use CloudCreativity\Modules\Infrastructure\OutboundEventBus\ClosurePublisher;

final class OutboundEventBusAdapter extends ClosurePublisher
    implements OutboundEventBusInterface
{
}
```

Create a closure-based publisher by providing it with the default closure for publishing events. You can then bind
specific closures to specific events, and add middleware to the publisher. Here's an example:

```php
namespace App\Modules\EventManagement\Infrastructure\OutboundEventBus;

use App\Modules\EventManagement\Application\Ports\Driven\OutboundEventBus\OutboundEventBusInterface;
use App\Modules\EventManagement\Infrastructure\GooglePubSub\EventSerializerInterface;
use App\Modules\EventManagement\Infrastructure\GooglePubSub\SecureTopicFactoryInterface;
use CloudCreativity\Modules\Contracts\Application\Messages\IntegrationEvent;
use CloudCreativity\Modules\Infrastructure\OutboundEventBus\Middleware\LogOutboundEvent;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;
use Psr\Log\LoggerInterface;
use VendorName\EventManagement\Shared\IntegrationEvents\V1\AttendeeTicketWasCancelled;

final readonly class OutboundEventBusAdapterProvider
{
    public function __construct(
        private SecureTopicFactoryInterface $topicFactory,
        private EventSerializerInterface $serializer,
        private LoggerInterface $logger,
    ) {
    }

    public function getEventBus(): OutboundEventBusInterface
    {
        $publisher = new OutboundEventBusAdapter(
            // default publisher
            fn: function (IntegrationEvent $event): void {
                $this->topicFactory->defaultTopic()->send([
                    'data' => $this->serializer->serialize($event),
                ]);
            },
            middleware: $middleware = new PipeContainer(),
        );
        
        /** Bind handlers for specific events (if needed) */
        $publisher->bind(
            AttendeeTicketWasCancelled::class,
            function (AttendeeTicketWasCancelled $event): void {
                $this->topicFactory->make('cancellations')->send([
                    'data' => $this->serializer->serialize($event),
                ]);
            },
        );

        /** Bind middleware factories */
        $middleware->bind(
            LogOutboundEvent::class,
            fn () => new LogOutboundEvent(
                $this->logger,
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

## Class-based Publishing

If you want to use class-based publishers, use our `ComponentPublisher` implementation. This is a similar approach to
the class-based handlers that are used in the application layer for the command, query and inbound event buses.

Define an adapter by extending this class:

```php
namespace App\Modules\EventManagement\Infrastructure\OutboundEventBus;

use App\Modules\EventManagement\Application\Ports\Driven\OutboundEventBus\OutboundEventBusInterface;
use CloudCreativity\Modules\Infrastructure\OutboundEventBus\ComponentPublisher;

final class OutboundEventBusAdapter extends ComponentPublisher
    implements OutboundEventBusInterface
{
}
```

### Event Handlers

Event handlers are classes that implement a `publish()` method. For example, we could define a default handler as
follows:

```php
namespace App\Modules\EventManagement\Infrastructure\OutboundEventBus\Publishers;

final class DefaultPublisher
{
    public function __construct(
        private SecureTopicFactoryInterface $topicFactory,
        private EventSerializerInterface $serializer,
    ) {
    }

    public function publish(IntegrationEventInterface $event): void
    {
        $this->topicFactory->defaultTopic()->send([
            'data' => $this->serializer->serialize($event),
        ]);
    }
}
```

And then we could also define a handler for a specific event:

```php
namespace App\Modules\EventManagement\Infrastructure\OutboundEventBus\Publishers;

use VendorName\EventManagement\Shared\IntegrationEvents\V1\AttendeeTicketWasCancelled;

final class AttendeeTicketWasCancelledPublisher
{
    public function __construct(
        private SecureTopicFactoryInterface $topicFactory,
        private EventSerializerInterface $serializer,
    ) {
    }

    public function publish(AttendeeTicketWasCancelled $event): void
    {
        $this->topicFactory->make('cancellations')->send([
            'data' => $this->serializer->serialize($event),
        ]);
    }
}
```

### Creating the Adapter

We can now create our adapter. This is injected with a handler container that knows how to construct each of your
handler classes. This container allows you to define a default handler to be used when no specific handler is bound to
an event. You can then bind specific handlers to specific events, and add middleware to the publisher.

```php
namespace App\Modules\EventManagement\Infrastructure\OutboundEventBus;

use App\Modules\EventManagement\Application\Ports\Driven\DependencyInjection\ExternalDependenciesInterface;
use App\Modules\EventManagement\Application\Ports\Driven\OutboundEventBus\OutboundEventBusInterface;
use App\Modules\EventManagement\Infrastructure\GooglePubSub\EventSerializerInterface;
use App\Modules\EventManagement\Infrastructure\GooglePubSub\SecureTopicFactoryInterface;
use CloudCreativity\Modules\Infrastructure\OutboundEventBus\Middleware\LogOutboundEvent;
use CloudCreativity\Modules\Infrastructure\OutboundEventBus\PublisherHandlerContainer;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;
use Psr\Log\LoggerInterface;
use VendorName\EventManagement\Shared\IntegrationEvents\V1\AttendeeTicketWasCancelled;

final readonly class OutboundEventBusAdapterProvider
{
    public function __construct(
        private SecureTopicFactoryInterface $topicFactory,
        private EventSerializerInterface $serializer,
        private LoggerInterface $logger,
    ) {
    }

    public function getEventBus(): OutboundEventBusInterface
    {
        $publisher = new OutboundEventBusAdapter(
            handlers: $handlers = new PublisherHandlerContainer(
                default: fn () => new Publishers\DefaultPublisher(
                    $this->topicFactory,
                    $this->serializer,
                ),
            ),
            middleware: $middleware = new PipeContainer(),
        );
        
        /** Bind handlers for specific events (if needed) */
        $handlers->bind(
            AttendeeTicketWasCancelled::class,
            fn () => new Publishers\AttendeeTicketWasCancelledPublisher(
                $this->topicFactory,
                $this->serializer,
            ),
        );

        /** Bind middleware factories */
        $middleware->bind(
            LogOutboundEvent::class,
            fn () => new LogOutboundEvent(
                $this->logger,
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

## Writing an Event Bus

If you do not want to use either of these implementations, you can write your own adapter. To do this, you need to
implement the following interface that was extended by the driven port:

```php
namespace CloudCreativity\Modules\Application\Ports\Driven\OutboundEventBus;

use CloudCreativity\Modules\Contracts\Application\Messages\IntegrationEvent;

interface EventPublisher
{
    /**
     * Publish an outbound integration event.
     *
     * @param IntegrationEvent $event
     * @return void
     */
    public function publish(IntegrationEvent $event): void;
}
```

:::tip
If you want your custom outbound event bus to use middleware, take a look at either of our two implementations to see
how that works.
:::

## Middleware

Our inbound event bus implementation gives you complete control over how to compose the handling of integration events,
via middleware. Middleware is a powerful way to add cross-cutting concerns to your event handling, such as logging.

To apply middleware to the outbound event bus, use the `through()` method - as shown in the earlier examples.
Middleware is executed in the order it is added.

### Logging

Use our `LogOutboundEvent` middleware to log when an integration event is published. It takes
a [PSR Logger](https://php-fig.org/psr/psr-3/).

```php
use CloudCreativity\Modules\Infrastructure\OutboundEventBus\Middleware\LogOutboundEvent;

$middleware->bind(
    LogOutboundEvent::class,
    fn () => new LogOutboundEvent(
        $this->dependencies->getLogger(),
    ),
);
```

The use of this middleware is identical to that described in the [Commands chapter.](../application/commands#logging)
See those instructions for more information, such as configuring the log levels.

Additionally, if you need to customise the context that is logged for an integration event then implement the
`ContextProviderInterface` on your integration event message. See the example in the
[Commands chapter.](../application/commands#logging)

### Writing Middleware

You can write your own middleware to suit your specific needs. Middleware is a simple invokable class, with the
following signature:

```php
namespace App\Modules\EventManagement\Application\Adapters\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Application\Messages\IntegrationEvent;
use CloudCreativity\Modules\Contracts\Infrastructure\OutboundEventBus\OutboundEventMiddleware;

final class MyMiddleware implements OutboundEventMiddleware
{
    /**
     * Execute the middleware.
     *
     * @param IntegrationEvent $event
     * @param Closure(IntegrationEvent): void $next
     * @return void
     */
    public function __invoke(
        IntegrationEvent $event,
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
`OutboundEventMiddlewareInterface`. Instead, use the same signature but change the event type-hint to the event class
your middleware is designed to be used with.
:::
