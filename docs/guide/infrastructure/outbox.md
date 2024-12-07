# Transactional Outbox

In the [Units of Work chapter](../application/units-of-work), we discussed how as your domain model grows in complexity,
one challenge you face is ensuring that all operations are completed in a consistent and reliable manner.

The unit of work pattern allows your application to clearly define a _transaction boundary_. This is the start and end
of a transaction, during which all state mutations either succeed or fail together.

When an operation requires multiple infrastructure services, ensuring data consistency becomes even more challenging.
This chapter describes the problem, and how you can solve it using a _transactional outbox_ pattern.

## Problem

The scenario we have used throughout this guide is an in-person event management bounded context. This has an attendee
aggregate root, through which we can cancel a ticket held by that attendee.

When we cancel the ticket, there may be multiple side effects that involve interaction with many different
infrastructure services. For example, we might want to:

- Notify other bounded contexts of the ticket cancellation, by publishing an integration event to an event bus.
- Recalculate attendance totals at the event, by queuing a command on a Redis queue for asynchronous processing.
- Send notifications to the attendee that their ticket has been cancelled, e.g. via email sent by Mailgun.

When we dispatch a "cancel attendee ticket" command, our command handler wraps the execution in a unit of work. This
ensures that all state mutations, e.g. modifying the attendees and tickets tables in our relational database, are
committed atomically.

However, it does not guarantee that the infrastructure side effects described above are atomic. The event bus, the Redis
queue and Mailgun will not be affected by the transaction.

This means if these side effects occur _within_ the transaction boundary, they will occur even if the transaction fails.

So what happens if we move them outside the transaction boundary, i.e. ensuring our listeners that trigger the side
effects are executed after the commit?

Now the problem is that we cannot guarantee _all_ the side-effects will occur. We might successfully publish to the
event bus, but if we encounter a temporary Redis failure both the asynchronous recalculation and the emailing will not
occur.

## Solution

This is where the _transactional outbox_ pattern comes to our rescue. It ensures atomicity and reliability by persisting
these operations within the same transaction as the domain state change.

Effectively, the outbox acts as a temporary storage location for the operations. By persisting them to an outbox that
uses the same storage as the domain state changes, we ensure that both the state changes and the actions all succeed or
fail together.

This gives us atomicity. We get reliability because we can then process each of the operations in isolation, with retry
and back-off capabilities.

This package does not provide an outbox implementation, as the exact implementation requires decisions based on your use
case. However, the below provides some broad guidance on implementations.

## Outbound Events

The [publishing events chapter](./publishing-events) described how integration events are published to an outbound event
bus.

Our recommended approach is to first place these events into an outbox. This means we need a driven port for the outbox:

```php
namespace App\Modules\EventManagement\Application\Ports\Driven\OutboundEvents;

use CloudCreativity\Modules\Contracts\Application\Messages\IntegrationEvent;

interface Outbox
{
    /**
     * Push the event into the outbox.
     *
     * @param IntegrationEvent $event
     * @return void 
     */
    public function push(IntegrationEvent $event): void;
}
```

Domain event listeners would then use this instead of publishing the event themselves. For example:

```php
namespace App\Modules\EventManagement\Application\Internal\DomainEvents\Listeners;

use App\Modules\EventManagement\Application\Ports\Driven\OutboundEvents\Outbox;
use App\Modules\EventManagement\Domain\Events\AttendeeTicketWasCancelled;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\UuidFactory;
use VendorName\EventManagement\Shared\IntegrationEvents\V1 as IntegrationEvents;

final readonly class PublishAttendeeTicketWasCancelled
{
    public function __construct(
        private UuidFactory $uuidFactory,
        private Outbox $outbox,
    ) {
    }

    public function handle(AttendeeTicketWasCancelled $event): void
    {
        $this->outbox->push(
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

When your outbox processor pulls this event from the outbox, it would then publish to the event bus.

## Queue Jobs

We also recommend any asynchronous work is committed in the same transaction as domain state changes.

PHP queue implementations typically provide a _database driver or connection_. This means you can actually achieve an
outbox pattern without having to write an outbox. Use the database driver, ensuring it is writing to the same database
as your domain state mutations. This means it will be atomic as it will persist in the same transaction.

If you are doing this, it is a good idea to make this _explicit_ in your code. Instead of naming your driven
port `Queue` - as suggested by the [Queues chapter](./queues) - call it `Outbox` for clarity:

```php
namespace App\Modules\EventManagement\Application\Ports\Driven\Queue;

use CloudCreativity\Modules\Contracts\Application\Ports\Driven\Queue;

interface Outbox extends Queue
{
}
```

If your PHP queue implementation does not support a database driver, or if you prefer to use a different technology, you
will need to implement an outbox as a bridge between your application layer and your queue.

The good news is you can easily build one of those using our [queue implementations](./queues) as the bridge between the
command bus and your actual queue. Again, we recommend being explicit about this in your code by naming your driven
port `Outbox` instead of `Queue`.

## External Systems

In the example earlier in this chapter, a side effect of cancelling a ticket was to send an email via Mailgun.

Our recommendation is to always use the outbox pattern for side effects that require interaction with third-party
systems. You can easily achieve this by queuing the work, and making use of a database driver for your queue
implementation.

What about microservices in your own architecture? Prefer loose coupling over direct coupling - so use outbound
integration events published via an outbox.

Alternatively, if you want to directly call the microservice, ask yourself whether this definitely needs to be
immediate? If not immediate, push the work to a queued command and leverage the queuing outbox approach described above.