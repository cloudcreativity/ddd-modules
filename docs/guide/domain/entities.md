# Entities & Aggregates

The business logic in your domain layer will be defined in terms of the following concepts:

- **Entity** - an object that has an identity and has state that can change in-line with business logic.
- **Aggregate Roots** - an entity that is the root of an aggregate. An aggregate is a group of entities that are treated
  as a single unit for the purpose of state changes in-line with business logic.
- **Value Object** - an object that has no identity and is immutable. It is used to describe a characteristic (or
  characteristics) of an entity, and to define data types specific to the domain that are not represented by primitives.
  (The [next chapter](./value-objects) covers these in detail.)

This package provides some tooling to help implement a bounded context's domain layer in terms of these concepts.
However, it is intentionally light-weight, because each domain should be modelled according to its own concerns.

## Entities

To define an entity, implement the `EntityInterface`. For example:

```php
namespace App\Modules\EventManagement\BoundedContext\Domain;

use CloudCreativity\Modules\Domain\EntityInterface;
use CloudCreativity\Modules\Domain\EntityTrait;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;

class BookableEvent implements EntityInterface
{
    use EntityTrait;

    public function __construct(
        IdentifierInterface $id,
        private readonly \DateTimeImmutable $startsAt,
        private readonly \DateTimeImmutable $endsAt,
        private bool $isCancelled = false,
    ) {
        $this->id = $id;
    }

    // ...methods
}
```

The `EntityInterface` defines that the implementing class is identifiable, and provides some helper methods for checking
if two entities are the same - `is()` and `isNot()`.

In some instances an entity may have a nullable identifier. For example, entities that can exist in the domain
layer before they are persisted for the first time. In this case, use the `EntityWithNullableIdTrait` instead of the
`EntityTrait`, for example:

```php
use CloudCreativity\Modules\Domain\EntityInterface;
use CloudCreativity\Modules\Domain\EntityWithNullableIdTrait;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;

class BookableEvent implements EntityInterface
{
    use EntityWithNullableIdTrait;

    public function __construct(
        private readonly ?IdentifierInterface $id,
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

To define an aggregate root, use the `AggregateInterface`:

```php
namespace App\Modules\EventManagement\BoundedContext\Domain;

use App\Modules\EventManagement\BoundedContext\Domain\ListOfTickets;
use App\Modules\EventManagement\BoundedContext\Domain\ValueObjects\Customer;
use CloudCreativity\Modules\Domain\AggregateInterface;
use CloudCreativity\Modules\Domain\EntityTrait;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;

class Attendee implements AggregateInterface
{
    use EntityTrait;

    public function __construct(
        private readonly IdentifierInterface $id,
        private readonly Customer $customer,
        private readonly ListOfTickets $tickets,
    ) {
        $this->id = $id;
    }

    // ...methods
}
```

The `AggregateInterface` extends the `EntityInterface` - there is no additional functionality. It is used to indicate
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
