# Domain Events

In DDD, domain events are events that occur within a domain, and are relevant to the business. Hopefully you surfaced
these events in an Event Storming session, held with your business stakeholders - in an ideal world, before you even
started writing code!

Domain events are a way to capture these business events in your code. They enable communication between the domain and
application layers of your bounded context. Example usages (described by this chapter) are:

1. Notifying changes to other entities or aggregate roots that are outside the scope of the aggregate root that raised
   the event.
2. Queuing work for asynchronous processing.
3. Publishing integration events for consumption by other bounded contexts.

## Domain vs Integration Events

The design philosophy of this package is to implement highly-encapsulated bounded contexts. This encapsulation also
applies to domain events.

In DDD Modules, when an aggregate or entity dispatches a domain event, the domain event is only subscribed to by
listeners in your application layer. These listeners then coordinate side-effects via driven ports (e.g. queues, event
buses, etc).

**Domain events never leave your application layer, and therefore cannot be subscribed to by the outside world.**

Instead, integration events are used to communicate between bounded contexts.
**Integration events are a different type of event, published to the outside world.**

This approach establishes a clear _separation of concerns_. A domain event describes the data contract for
communication _within_ the bounded context - between the domain and the application layers. An integration event
describes the data contract for communication _outside_ the bounded context. Each has a _single responsibility_.

In fact, a domain event may not even have an equivalent integration event - for instance, if it is an event that does
not need to be communicated outside the bounded context. Or you may have multiple integration events that are triggered
by a single domain event.

## Defining Events

Your domain events are simple classes that define the data contract for the event. They should be immutable, and
contain only data that is relevant to the event. They must not contain any business logic.

```php
namespace App\Modules\EventManagement\Domain\Events;

use App\Modules\EventManagement\Domain\Enums\CancellationReasonEnum;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;

final readonly class AttendeeTicketWasCancelled implements
    DomainEvent
{
    public function __construct(
        public Identifier $eventId,
        public Identifier $attendeeId,
        public Identifier $ticketId,
        public CancellationReasonEnum $reason,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
```

The `DomainEvent` interface is intentionally light-weight. Its main intention is to signal that the implementing class
is a domain event. The only method it defines is `getOccurredAt()`, which returns the date and time the event occurred.

:::warning
You should avoid attaching the entity or aggregate that dispatched the event to the domain event itself.
Although it is tempting to do so, it is not a good practice.

The reason is that the entity or aggregate has public methods that trigger mutations of its state. These should not be
exposed to listeners that have subscribed to the domain event.

Why? :thinking:

When a domain event is dispatched by an entity/aggregate, it is signalling that something has _happened_ to it - and
importantly that it is now in a **settled state**. Therefore, no listener attaching to the event should need to trigger
another mutation to the same aggregate. They should exclusively trigger _side-effects_ to things that live _outside_ the
originating aggregate or entity.
:::

## Dispatching Events

### Event Dispatcher

To dispatch domain events, you will need a domain event dispatcher. While there is a generic interface for this in the
package, our domain layer needs its _specific_ instance of the domain dispatcher. This is indicated by extending the
interface in your domain layer:

```php
namespace App\Modules\EventManagement\Domain\Events;

use CloudCreativity\Modules\Contracts\Domain\Events\DomainEventDispatcher as BaseDispatcher;

interface DomainEventDispatcher extends BaseDispatcher
{
}
```

Here we are using the _dependency inversion_ principle. Our domain layer defines that it needs an event dispatcher, but
it does not provide the concrete implementation.

Instead, a dispatcher is provided by the application layer. By inverting the dependency, we allow domain events to reach
the application layer. Here listeners can subscribe to events, and coordinate infrastructure concerns via driven ports
in the application layer.

This package ships with several concrete dispatcher implementations. These dispatchers are covered in
the [domain events chapter in the application layer.](../application/domain-events) Our dispatcher implementations allow
listener classes to subscribe to events. This is also covered in the linked chapter.

### Domain Service

To dispatch events, you will need a [Domain Service](./services) that exposes a domain event dispatcher.
The pattern for exposing domain services is described in the linked chapter. But an example of how we would do this
for domain events is as follows:

```php
namespace App\Modules\EventManagement\Domain;

use App\Modules\EventManagement\Domain\Events\DomainEventDispatcher;
use Closure;

final class Services
{
    /**
     * @var Closure(): DomainEventDispatcher|null
     */
    private static ?Closure $events = null;

    /**
     * @param Closure(): DomainEventDispatcher $events
     */
    public static function setEvents(Closure $events): void
    {
        self::$events = $events;
    }

    public static function getEvents(): DomainEventDispatcher
    {
        assert(
            self::$events !== null,
            'Expecting a domain event dispatcher factory to be set.',
        );

        return (self::$events)();
    }

    public static function tearDown(): void
    {
        self::$events = null;
    }
}
```

### Firing Events

Within an entity or aggregate, you can now dispatch an event as follows:

```php
namespace App\Modules\EventManagement\Domain;

use App\Modules\EventManagement\Domain\{
   Enums\CancellationReasonEnum,
   Events\AttendeeTicketWasCancelled,
};
use CloudCreativity\Modules\Contracts\Domain\AggregateRoot;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;

class Attendee implements AggregateRoot
{
    // ...other methods

    public function cancelTicket(
        Identifier $ticketId,
        CancellationReasonEnum $reason,
    ): void
    {
        $ticket = $this->tickets->findOrFail($ticketId);

        if ($ticket->isNotCancelled()) {
            $ticket->markAsCancelled($reason);

            Services::getEvents()->dispatch(new AttendeeTicketWasCancelled(
                eventId: $this->eventId,
                attendeeId: $this->id,
                ticketId: $ticketId,
                reason: $reason,
            ));
        }
    }
}
```

## Use Cases

This section covers examples of typical use cases for the consumption of domain events.

### Domain Side Effects

Use domain events to trigger side-effects in other entities or aggregate roots that are outside the scope of the
aggregate root that raised the event. This ensures your entities remain encapsulated and free from dependencies on
other entities.

:::warning
This must only be used for side effects in _other_ aggregates or entities - not entities contained within the aggregate
that emits the event.

Why? A domain aggregate must complete all state changes to entities contained within the aggregate root _before_
dispatching a domain event. This is because the aggregate's state must be _settled_ before any domain event is
dispatched.
:::

Using our attendee ticket cancellation event as an example, we might have a separate aggregate that manages reporting
of ticket sales and cancellations. As a result of an attendee cancelling their ticket, we need this reporting
aggregate to update its state as a side-effect.

Our listener for this scenario might look like this:

```php
namespace App\Modules\EventManagement\Application\Internal\DomainEvents\Listeners;

use App\Modules\EventManagement\Domain\Events\AttendeeTicketWasCancelled;
use App\Modules\EventManagement\Application\Ports\Driven\Persistence\TicketSalesReportRepository;

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

:::tip
Notice the listener is correctly in the application layer, because it combines knowledge of the domain layer
(the report entity) with the driven port (the repository interface) needed to persist the changes.

The advantage here is that if your application layer uses a _unit of work_, and you are using our "unit of work aware"
domain event dispatcher, the side effect will be part of the same transaction as the mutation of the aggregate that
emitted the event. This is good practice, because it means the report recalculation will only be committed if the
changes to the originating aggregate are also committed. See
the [Units of Work Chapter](../application/units-of-work.md) for more information.
:::

### Asynchronous Processing

You can also use domain events to queue work for your bounded context to process asynchronously.

For example, let's say that instead of recalculating the ticket sales report synchronously - as shown in the previous
listener example - we instead wanted to push the work onto a queue for asynchronous processing. This work is a
_command_ message, because it will alter the state of the bounded context.

For this, we need a listener in the application layer that will queue a command for asynchronous processing. For
example:

```php
namespace App\Modules\EventManagement\Application\Internal\DomainEvents\Listeners;

use App\Modules\EventManagement\Application\Ports\Driving\CommandBus\InternalCommandBus;
use App\Modules\EventManagement\Application\Internal\Commands\RecalculateSalesAtEvent\RecalculateSalesAtEventCommand;
use App\Modules\EventManagement\Domain\Events\AttendeeTicketWasCancelled;

final readonly class QueueTicketSalesReportRecalculation
{
    public function __construct(private InternalCommandBus $bus)
    {
    }

    public function handle(AttendeeTicketWasCancelled $event): void
    {
        $this->bus->queue(new RecalculateSalesAtEventCommand(
            $event->eventId,
        ));
    }
}
```

:::tip
The pattern of queuing work internal to a bounded context is described in our
[Asynchronous Processing chapter](../application/asynchronous-processing) in the infrastructure layer.
:::

### Publishing Integration Events

Publishing integration events should always be implemented as a domain event listener. This is because an integration
event is a _consequence_ of a domain event.

:::info
This makes sense when you think of it in terms of our bounded context's layers.

An integration event is a message that is published to the outside world via a driven port in the application layer. The
application layer can depend on the domain layer - but not the other way round.

Therefore, there is no direct way for a domain entity to publish an integration event. It must always be a side effect
of a domain event.
:::

Our listener to do this might look like this:

```php
namespace App\Modules\EventManagement\Application\Internal\DomainEvents\Listeners;

use App\Modules\EventManagement\Application\Ports\Driven\OutboundEventBus\OutboundEventBus;
use App\Modules\EventManagement\Domain\Events\AttendeeTicketWasCancelled;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\UuidFactory;
use VendorName\EventManagement\Shared\IntegrationEvents\V1 as IntegrationEvents;

final readonly class PublishAttendeeTicketWasCancelled
{
    public function __construct(
        private UuidFactory $uuidFactory,
        private OutboundEventBus $publisher,
    ) {
    }

    public function handle(AttendeeTicketWasCancelled $event): void
    {
        $this->publisher->publish(
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
In the example, there is a one-to-one relationship between the domain and integration events. In fact, they even have
the same name.

This is just an example. Remember you can build your domain according to its own business logic. For example, you might
have a domain event that triggers several integration events. Or you might have a domain event that does not trigger
any integration events at all.
:::

## Testing

Your testing of aggregates and entities should encompass asserting that they dispatch the correct domain events, in the
correct scenarios. If you are following our domain services pattern shown earlier in this chapter, this is easy to do.

We also provide a `FakeDomainEventDispatcher` that you can use in your tests. This is a simple implementation of the
domain event dispatcher that allows you to assert that events are dispatched.

Putting the two together, the following is a good pattern for testing domain events:

```php
use App\Modules\EventManagement\Domain\Events\DomainEventDispatcher as IDomainEventDispatcher;
use CloudCreativity\Modules\Testing\FakeDomainEventDispatcher;

class AttendeeTest extends TestCase
{
    private FakeDomainEventDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->dispatcher = new class () extends FakeDomainEventDispatcher implements IDomainEventDispatcher {};
        
        Services::setEvents(fn () => $this->dispatcher);
    }

    protected function tearDown(): void
    {
        Services::tearDown();
        unset($this->dispatcher);
        parent::tearDown();
    }
}
```

Then you can assert that events were dispatched via the fake dispatcher's `$events` property:

```php
$this->assertCount(2, $this->dispatcher->events);
```

If you are only expecting exactly one event to be dispatched, use the `sole()` helper method:

```php
$expected = new AttendeeTicketWasCancelled(
    eventId: $eventId,
    attendeeId: $attendeeId,
    ticketId: $ticketId,
    reason: $reason,
);

$this->assertEquals($expected, $this->dispatcher->sole());
```
