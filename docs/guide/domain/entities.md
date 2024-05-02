# Entities & Aggregates

The business logic in your domain layer will be defined in terms of the following concepts:

- **Entity** - an object that has an identity and has state that can change in-line with business logic.
- **Aggregate Roots** - an entity that is the root of an aggregate. An aggregate is a group of entities that are treated
  as a single unit for the purpose of state changes in line with business logic.
- **Value Object** - an object that has no identity and is immutable. It is used to describe a characteristic (or
  characteristics) of an entity, and to define data types specific to the domain that are not represented by primitives.
  (The [next chapter](./value-objects) covers these in detail.)

This package provides some tooling to help implement a bounded context's domain layer in terms of these concepts.
However, it is intentionally light-weight, because each domain should be modelled according to its own concerns.

## Entities

To define an entity, implement the `Entity` interface. For example:

```php
namespace App\Modules\EventManagement\Domain;

use CloudCreativity\Modules\Contracts\Domain\Entity;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Domain\IsEntity;

class BookableEvent implements Entity
{
    use IsEntity;

    public function __construct(
        Identifier $id,
        private readonly \DateTimeImmutable $startsAt,
        private readonly \DateTimeImmutable $endsAt,
        private bool $isCancelled = false,
    ) {
        $this->id = $id;
    }

    // ...methods
}
```

The entity interface defines that the implementing class is identifiable, and provides some helper methods for checking
if two entities are the same - `is()` and `isNot()`.

In some instances an entity may have a nullable identifier. For example, entities that can exist in the domain
layer before they are persisted for the first time. In this case, use the `EntityWithNullableIdTrait` instead of the
`EntityTrait`, for example:

```php
use CloudCreativity\Modules\Contracts\Domain\Entity;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Domain\IsEntityWithNullableId;

class BookableEvent implements Entity
{
    use IsEntityWithNullableId;

    public function __construct(
        private readonly ?Identifier $id,
        private readonly \DateTimeImmutable $startsAt,
        private readonly \DateTimeImmutable $endsAt,
        private bool $isCancelled = false,
    ) {
        $this->id = $id;
    }

    // ...methods
}
```

This trait provides a method for setting the identifier - `setId()`.

## Aggregates

To define an aggregate root, use the `Aggregate`:

```php
namespace App\Modules\EventManagement\Domain;

use App\Modules\EventManagement\Domain\ValueObjects\Customer;
use CloudCreativity\Modules\Contracts\Domain\Aggregate;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Domain\IsEntity;

class Attendee implements Aggregate
{
    use IsEntity;

    public function __construct(
        private readonly Identifier $id,
        private readonly Customer $customer,
        private readonly ListOfTickets $tickets,
    ) {
        $this->id = $id;
    }

    // ...methods
}
```

The aggregate interface extends the entity interface - there is no additional functionality. It is used to indicate
that the entity is the root of an aggregate. In the example, the `Attendee` aggregate root has a `ListOfTickets`,
which is a value object that holds a list of `Ticket` entities.

## Identifiers

In both the entity and the aggregate root, the identifier is type-hinted as `IdentifierInterface`. This is intentional,
as it prevents a concern of the infrastructure persistence layer from leaking into your domain.

For example, it can be tempting to type-hint the identifier as `int` if your persistence layer uses an auto-incrementing
integer as the primary key. However, this is a leaky abstraction, as it means that the domain layer is now coupled to
the persistence layer - as it knows how identifiers are issued and persisted. This coupling is the wrong way around:
the domain layer should not be coupled to any other layer.

To prevent this coupling, this package provides an `IdentifierInterface` that can be used in the domain layer. It then
provides tools for working with identifiers in other layers, where you need to work with _expected identifier types_,
e.g. an integer where we know the persistence layer uses an auto-incrementing integer as the primary key.

See the [Identifiers chapter](../toolkit/identifiers) for more details.

## Invariants

Entities and aggregates should be designed to maintain invariants. An invariant is a condition that must always be true
for the entity or aggregate to be in a valid state.

### Constructor State

Maintaining invariants means that an entity or aggregate must never be constructed in an invalid state. This means you
will need to enforce the invariant within the constructor.

For example, our `Attendee` aggregate root should always have at least one ticket. This is an invariant that we can
enforce in the constructor:

```php
use CloudCreativity\Modules\Toolkit\Contracts;

class Attendee implements AggregateInterface
{
    use EntityTrait;

    public function __construct(
        private readonly IdentifierInterface $id,
        private readonly Customer $customer,
        private readonly ListOfTickets $tickets,
    ) {
        Contracts::assert(
            $this->tickets->isNotEmpty(),
            'Attendee must have at least one ticket.',
        );

        $this->id = $id;
    }

    // ...methods
}
```

:::tip
See the [Assertions Chapter](../toolkit/assertions) for an explanation of the `Contract::assert()` helper.
:::

### Mutating State

Additionally, your entities and aggregates should never enter an invalid state. This means that you should
not provide public "setters" for changing properties on the entity or aggregate. Instead, you should provide methods
that represent the _business logic_ that can change the state of the entity or aggregate.

These methods are better than setters, as they allow the "complete" state of the mutation to be provided at once,
through the method arguments. In comparison, "setter" methods typically allow the state to be changed incrementally,
one property at a time - which risks the entity or aggregate entering an invalid state between each change.

:::tip
If your state changing method requires a lot of inputs, consider using a value object to encapsulate the inputs -
rather than having a long list of method arguments. This also means the value object can encapsulate the logic of
ensuring the inputs are an invariant, which can be reused in other parts of your domain.
:::

For example, if an `Attendee` can cancel a ticket, you might provide a `cancelTicket()` method:

```php
public function cancelTicket(
    IdentifierInterface $ticketId,
    CancellationReasonEnum $reason,
): void
{
    $ticket = $this->tickets->findOrFail($ticketId);

    if ($ticket->isNotCancelled()) {
        $ticket->markAsCancelled($reason);

        Services::getEvents()->dispatch(new AttendeeTicketWasCancelled(
            attendeeId: $this->id,
            ticketId: $ticketId,
            reason: $reason,
        ));
    }
}
```

:::tip
As shown in the example, this also allows your entity to emit domain events when its state changes.
See the [Domain Events chapter](./events) for more detail on this topic.
:::

## Serialization

It can be tempting to implement PHP's `JsonSerializable` interface on aggregates or entities, so that they can
be easily serialized to JSON. **This must never be implemented on a domain aggregate or entity.**

The reason is that when you implement serialization logic on an aggregate or entity, you have no context to understand
_why_ the object is being serialized, and _how_ it should be serialized.

For example, if you had both a v1 and v2 version of your API, which is the entity being serialized for? If you have
a separate "backend-for-frontend", is the entity being serialized for that? Or is it being serialized for storage by
your infrastructure's persistence layer?

The answer is that only the _presentation_ or _infrastructure_ layer knows - as JSON can be either a data delivery
mechanism or a storage format. As we know, the domain layer is the inner-most layer, that should have no knowledge of
other layers.

Therefore, an aggregate or entity can never be serialized by the domain layer. We must leave that to the concern of
the presentation or infrastructure layer.

:::info
Another reason why an aggregate or entity can never implement serialization logic is that they are your domain's
"write model". They represent the structure required to execute business logic and mutate state.

In contrast, JSON returned by your presentation layer represents your "read model". In the CQRS pattern, the read
model is obtained by dispatching a query - which should never return domain entities and aggregates. Instead, they
should return immutable read models that represent the data model of the current state of your domain. It is these
read models that should be serialized to JSON.

Learn about [read models in the Query chapter.](../application/queries#read-models)
:::