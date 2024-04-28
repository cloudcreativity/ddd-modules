# Persistence

Persistence is the ability of data to outlive the process that created it. In the context of software, this means that
data is stored somewhere, and can be retrieved later. This is a fundamental concept, as it allows data to be stored and
retrieved across different runs of the program, or even across different programs.

This package does not provide any specific persistence concerns. This is because persistence is highly dependent on the
specific use case and the infrastructure of the application. Instead, this chapter provides some guidance and best
practices on how to implement persistence in domain-driven way.

## Read vs Write Models

In the context of Domain Driven Design, persistence is the mechanisms by which:

- aggregate roots and entities are stored, updated and retrieved; and
- current state is questioned (queried) and answered via [read models.](../application/queries#read-models)

This is a _separation of concerns_ between your domain's "write model" (aggregate roots and entities mutated via
commands) and the "read model" (the read-only data returned by queries).

Operations that change the state of the domain must always go via aggregate roots and entities. They define the business
logic for the mutation, enforce invariants, and emit events that signal the consequence of the mutation.

Operations that read the state of the domain are a separate concern, that is likely to be optimized for performance and
scalability. They can expose the state of the domain in a way that is more suitable for querying, and can be optimized
for the specific use case.

:::tip
The biggest tip we can give for implementing persistence in a DDD application is to embrace this separation between read
and write models. Initially it can feel like duplicated effort, especially if your read models are very similar to your
aggregate roots and entities.

However, as your application increases in complexity and scales, you will appreciate the flexibility and performance
benefits of this separation.
:::

### Example

In a lot of chapters in this guide we've used the example of an attendee at an in-person event, who has one-to-many
tickets that admit them to that event.

In this example, the business logic of the domain means that the state of an attendee is derived from their tickets.
Therefore, in our write model the attendee is an aggregate root that contains ticket entities.

Any changes that we need to make to a ticket - e.g. cancelling it - are implemented via the aggregate root. This ensures
that the business rules are enforced and that the state of the domain concept (attendee that has tickets) is consistent.

:::info
In this scenario, our ticket entity never needs an "attendee id" property. That's because the entity is only accessed
via the aggregate root - which is the attendee. The relationship is defined by the aggregate containing the entity, not
a property on the contained entity.
:::

However, what if we want to build a feature that shows all the tickets at an event?

If we used the data structure defined by our write model, we would have to load all attendees and their tickets, and
then pull the tickets out of the attendees. This is likely to be inefficient and would not scale well. Additionally, if
our ticket entity does not have an attendee identifier on it, how are we going to hold that bit of information for each
ticket?

The problem here is that this approach attempts to use the write model to return information about the domain. There is
additional complexity in that the query - "give me all tickets at an event" - does not match the domain concept of
tickets being owned by the attendee aggregate root.

The solution is to have a completely separate persistence implementation that is optimised for the querying of tickets.
This would return ticket read models, that contain the properties needed to answer the query. This read model can be
entirely independent of an attendee model. And it allows us to have properties that are not on our ticket entity - for
example, an attendee identifier to indicate the relationship.

### Read vs Write Ports

Using this example, we can see that our application would actually need two separate driven ports.

The first port would need to be used by our "cancel ticket" command to retrieve the attendee, then persist the mutated
state back:

```php
namespace App\Modules\EventManagement\Application\Posts\Driven\Persistence\AttendeeRepositoryInterface;

use App\Modules\EventManagement\Domain\Attendee;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;

interface AttendeeRepositoryInterface
{
    public function findOrFail(IdentifierInterface $attendeeId): Attendee;
    public function update(Attendee $attendee): void;
}
```

The second port would be used by our "get all tickets" query:

```php
namespace App\Modules\EventManagement\Application\Posts\Driven\Persistence\ReadModels\V1\TicketModelRepositoryInterface;

use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;
use VendorName\EventManagement\Shared\ReadModels\V1\TicketModel;

interface TicketModelRepositoryInterface
{
    /**
     * @param IdentifierInterface $eventId
     * @return list<TicketModel>
     */
    public function getByEventId(IdentifierInterface $eventId): array;
}
```

## Aggregate Repositories

In the context of Domain Driven Design, aggregate repositories are the mechanism by which aggregate roots are stored,
updated and retrieved.

The biggest tip we can give is to ensure that your aggregate repositories also store the entities contained within the
aggregate root. This is because the aggregate root is the consistency boundary for the entities it contains. This means
that the repository that stores it must ensure that it is stored and retrieved in a consistent state, and it can only
ensure this if it also owns storing and retrieving the contained entities.

### Aggregate Port

To return to our example of an attendee aggregate root that contains ticket entities, the attendee repository would also
store and retrieve the ticket entities. This means we only need one port:

```php
namespace App\Modules\EventManagement\Application\Posts\Driven\Persistence\AttendeeRepositoryInterface;

use App\Modules\EventManagement\Domain\Attendee;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;

interface AttendeeRepositoryInterface
{
    public function findOrFail(IdentifierInterface $attendeeId): Attendee;
    public function update(Attendee $attendee): void;
}
```

There is no driven port for storing ticket entities, because they are not stored and retrieved in isolation.

### Aggregate Adapter

When you come to write the adapter implementation of this port, your adapter can have knowledge of how the attendee and
tickets are physically stored.

If we were using MySQL as the storage mechanism, we could have chosen to store the attendee and tickets in separate
tables. The adapter would then be responsible for ensuring that the attendee and tickets are stored and retrieved across
both tables.

For example:

```php
final class MySQLAttendeeRepository implements AttendeeRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function findOrFail(IdentifierInterface $attendeeId): Attendee
    {
        // fetch the attendee from the attendees table
        // fetch the tickets from the tickets table
        // create ticket entities
        // create the attendee, injecting ticket entities
    }

    public function update(Attendee $attendee): void
    {
        // update the attendee in the attendees table
        // update the tickets in the tickets table
    }
}
```

:::tip
We do not need to use a MySQL transaction within the repository, because our command handlers should use
a [unit of work](../application/units-of-work) to ensure that multiple write operations are atomic.

The command handler is the correct place to define the transaction boundary, because the attendee repository may not be
the only infrastructure concern that is writing state to the database.
:::

## ORMs and Active Records

A common question about domain driven design is: "can I use an ORM or Active Record pattern with DDD?"

The answer is yes, but with caveats.

:::info
ORM is an acronym for Object-Relational Mapping. It is a technique that allows you to query and manipulate data from a
database using an object-oriented paradigm. A PHP example is Doctrine ORM.

Active Record is a design pattern that combines data access and mutation in a single object. A PHP example is Laravel's
Eloquent models.
:::

If you choose to use an ORM or Active Record pattern, you should still ensure that your domain model is the primary
concern of your application. Your domain model should never be coupled to the ORM or Active Record pattern.

In effect, this means the use of an ORM or Active Record becomes an internal detail of your infrastructure layer. As the
domain layer cannot depend on the infrastructure layer, your domain model and logic should not be aware of it.

### Laravel Example

For example, if you are using Laravel, you may choose to use Eloquent models. This is acceptable, as long as the use of
Eloquent models does not leak outside the infrastructure layer.

So for our attendee aggregate root, we would use Eloquent to load the attendee and related tickets from the database,
but then map these models to our attendee aggregate root and ticket entities.

Here's an illustrative example:

```php
use App\Modules\EventManagement\Domain\Attendee as AttendeeAggregate;
use App\Modules\EventManagement\Domain\Enums\TicketStatusEnum;
use App\Modules\EventManagement\Domain\Ticket as TicketEntity;
use App\Modules\EventManagement\Domain\ListOfTickets;
use App\Models\Attendee as AttendeeModel;
use App\Models\Attendee as TicketModel;
use CloudCreativity\Modules\Toolkit\Identifiers\IntegerId;

final class EloquentAttendeeRepository implements AttendeeRepositoryInterface
{
    private array $cache = [];

    public function findOrFail(IdentifierInterface $attendeeId): AttendeeAggregate
    {
        $attendeeId = IntegerId::from($attendeeId);
        $model = AttendeeModel::with('tickets')->findOrFail($attendeeId->value);
        $this->cache[$attendeeId->value] = $model;
        
        $tickets = $model->tickets->map(function (TicketModel $ticket) {
            return new TicketEntity(
                new IntegerId($ticket->getKey()),
                TicketStatusEnum::from($ticket->status),
            );
        });
        
        return new AttendeeAggregate(
            $attendeeId,
            new ListOfTickets(...$tickets),
        );
    }

    public function update(Attendee $attendee): void
    {
        $attendeeId = IntegerId::from($attendee->getId());
        $model = $this->cache[$attendeeId->value] ?? null;
        
        assert($model instanceof AttendeeModel);
        
        // update the attendee model
        // update the ticket models
    }
}
```

Likewise, if we had a repository for ticket read models, we could load Eloquent ticket models and then map them to our
ticket read model.

:::tip
Whether this mapping is efficient enough for your use case, only you can decide.

When first implementing your bounded context, you might find it easy to start with Eloquent. For example, loading an
attendee Eloquent model with ticket models eager loaded is straight forward.

However, under the hood this is potentially inefficient. Eloquent is loading a row from a database, then hydrating an
Eloquent model - for you to then immediately map that to an aggregate root.

As your application scales, you might find that the performance of this approach is not sufficient. At that point, you
can choose to implement a repository that directly uses the database row. The good thing hexagonal architecture is that
you can do this without changing your domain model, logic or application layer.
:::