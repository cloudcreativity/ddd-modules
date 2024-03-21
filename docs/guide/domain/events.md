# Domain Events

In DDD, domain events are events that occur within a domain, and are relevant to the business. Hopefully you surfaced
these events in an Event Storming session, held with your business stakeholders - in an ideal world, before you even
started writing code!

Domain events are a way to capture these business events in your code. They enable communication between different
parts of your bounded context. Example usages (described by this chapter) are:

1. Notifying changes to other entities or aggregate roots that are outside the scope of the aggregate root that raised
   the event.
2. Queuing work for asynchronous processing.
3. Publishing integration events to other bounded contexts.

## Domain vs Integration Events

The design philosophy of this package is to implement highly-encapsulated bounded contexts. This encapsulation also
applies to domain events.

In DDD Modules, when an aggregate or entity dispatches a domain event, the domain event is only subscribed to by other
parts of the same bounded context (aka module). **Domain events are never published outside the module, i.e. cannot
be subscribed to by the outside world.**

Instead, integration events are used to communicate between bounded contexts.
**Integration events are a different type of event, published to the outside world.**

This approach establishes a clear _separation of concerns_. A domain event describes the data contract for
communication _within_ the bounded context, and an integration event describes the data contract for communication
_outside_ the bounded context. Each has a _single responsibility_.

In fact, a domain event may not even have an equivalent integration event - for instance, if it is an event that does
not need to be communicated outside the bounded context.

## Defining Events

Your domain events are simple classes that define the data contract for the event. They should be immutable, and
contain only data that is relevant to the event. They must not contain any business logic.

```php
namespace App\Modules\EventManagement\BoundedContext\Domain\Events;

use App\Modules\EventManagement\BoundedContext\Domain\{
    Enums\CancellationReasonEnum,
    Events\AttendeeTicketWasCancelled,
};
use CloudCreativity\Modules\Domain\Events\DomainEventInterface;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;

final readonly class AttendeeTicketWasCancelled implements
    DomainEventInterface
{
    public function __construct(
        public IdentifierInterface $eventId,
        public IdentifierInterface $attendeeId,
        public IdentifierInterface $ticketId,
        public CancellationReasonEnum $reason,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
```

The `DomainEventInterface` is intentionally light-weight. Its main intention is to signal that the implementing class
is a domain event. The only method it defines is `occurredAt()`, which returns the date and time the event occurred.

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

### Domain Service

To dispatch events, you will need a [Domain Service](./services) that exposes our domain event dispatcher interface.
The pattern for exposing domain services is described in the linked chapter. But an example of how we would do this
for domain events is as follows:

```php
namespace App\Modules\EventManagement\BoundedContext\Domain;

use Closure;
use CloudCreativity\Modules\Domain\Events\DispatcherInterface;

final class Services
{
    /**
     * @var Closure(): DispatcherInterface|null
     */
    private static ?Closure $events = null;

    /**
     * @param Closure(): DispatcherInterface $events
     */
    public static function setEvents(Closure $events): void
    {
        self::$events = $events;
    }

    public static function getEvents(): DispatcherInterface
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
namespace App\Modules\EventManagement\BoundedContext\Domain;

use App\Modules\EventManagement\BoundedContext\Domain\{
    Enums\CancellationReasonEnum,
    Events\AttendeeTicketWasCancelled,
};
use CloudCreativity\Modules\Domain\AggregateInterface;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;

class Attendee implements AggregateInterface
{
    // ...other methods

    public function cancelTicket(
        IdentifierInterface $ticketId,
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

### Available Dispatchers

This package ships with two concrete domain dispatcher implementations:

1. Unit of Work Aware Dispatcher - this is the recommended dispatcher.
2. Deferred Dispatcher - useful if you have a module that is not using a unit of work.

As domain events trigger side-effects that typically require some interaction with your module's infrastructure layer,
both of these concrete implementations are provided by the infrastructure layer. They are therefore covered in that
section of the documentation.

### Subscribing to Events

Our dispatcher implementations allow listener classes to subscribe to events. The dispatchers expose a `listen()`
method that allows you to attach listeners to specific domain events. This is covered in the documentation on each
dispatcher.

## Use Cases

This section covers examples of typical use-cases for the consumption of domain events.

### Domain Side-Effects

Use domain events to trigger side-effects in other entities or aggregate roots that are outside the scope of the
aggregate root that raised the event. This ensures your entities remain encapsulated and free from dependencies on
other entities.

:::warning
This must only be used for _side effects_. A domain aggregate must complete all state changes to entities contained
within the aggregate root _before_ dispatching a domain event. This is because the aggregate's state must be _settled_
before any domain event is dispatched.
:::

Using our attendee ticket cancellation event as an example, we might have a separate aggregate that manages reporting
of ticket sales and cancellations. As a result of an attendee cancelling their ticket, we need this reporting
aggregate to update its state as a side-effect.

Our listener for this scenario might look like this:

```php
namespace App\Modules\EventManagement\BoundedContext\Infrastructure\DomainEventListeners;

use App\Modules\EventManagement\BoundedContext\Domain\Events\AttendeeTicketWasCancelled;
use App\Modules\EventManagement\BoundedContext\Infrastructure\Persistence\TicketSalesReportRepository;

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
Notice this listener is intentionally in the infrastructure layer, because it combines knowledge of the domain layer
(the report entity) with the infrastructure layer (the persistence repository).

The advantage here is that if your persistence layer uses a _unit of work_, and you are using our "unit of
work aware" dispatcher, the side-effect will be part of the same transaction as the original domain event. This is
good practice, because it means the report recalculation will only be committed if the original changes to the
originating aggregate are also committed. See the [Units of Work Chapter](../infrastructure/units-of-work) for more
information.
:::

### Asynchronous Processing

You can also use domain events to queue work for your bounded context to process asynchronously.

For example, let's say that instead of recalculating the ticket sales report synchronously - as shown in the previous
listener example - we instead wanted to push the work onto a queue for asynchronous processing. This work is a
_command_ message, because it will alter the state of the bounded context.

For this, we need a listener in the application layer that will queue a command for asynchronous processing. For
example:

```php
namespace App\Modules\EventManagement\BoundedContext\Application\Listeners;

use App\Modules\EventManagement\BoundedContext\Application\Commands\RecalculateSalesAtEventCommand;
use App\Modules\EventManagement\BoundedContext\Domain\Events\AttendeeTicketWasCancelled;
use CloudCreativity\Modules\Infrastructure\Queue\QueueInterface;

final readonly class QueueTicketSalesReportRecalculation
{
    public function __construct(private QueueInterface $queue)
    {
    }

    public function handle(AttendeeTicketWasCancelled $event): void
    {
        $this->queue->push(new RecalculateSalesAtEventCommand(
            $event->eventId,
        ));
    }
}
```

:::tip
The pattern of queuing work _within_ a bounded context is described in our
[Asynchronous Processing chapter](../infrastructure/queues) in the infrastructure layer.

Notice this listener is in the application layer. This is because it is combining an application concern (the command
message) with an infrastructure component (the queue).
:::

### Publishing Integration Events

Publishing integration events should always be implemented as a domain event listener. This is because an integration
event is a _consequence_ of a domain event.

:::info
This makes sense when you think of it in terms of our bounded context's layers.

An integration event is a message, defined in the application layer, and published to the outside world via the
application's event bus. The application layer can depend on the domain layer - but not the other way round.

Therefore, there is no direct way for a domain entity to publish an integration event. It must always be a side-effect
of a domain event.
:::

Again, to do this we would need a listener in the application layer - because it will need to combine listening for
the domain event with publishing the integration event to the event bus (which is an application layer component).

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

In your aggregate test case, setup and tear down the services:

```php
class AttendeeTest extends TestCase
{
    private DispatcherInterface&MockObject $events;

    protected function setUp(): void
    {
        parent::setUp();
        $this->events = $this->createMock(DispatcherInterface::class);
        Services::setEvents(fn() => $this->events);
    }

    protected function tearDown(): void
    {
        Services::tearDown();
        parent::tearDown();
    }
}
```

Then in the relevant test:

```php
$this->events
    ->expects($this->once())
    ->method('dispatch')
    ->with($this->callback(
        function (AttendeeTicketWasCancelled $event) use ($eventId, $attendeeId, $ticketId, $reason): bool {
            $this->assertObjectEquals($eventId, $event->eventId);
            $this->assertObjectEquals($attendeeId, $event->attendeeId);
            $this->assertObjectEquals($ticketId, $event->ticketId);
            $this->assertSame($reason, $event->reason);
            return true;
        },
    ));
```
