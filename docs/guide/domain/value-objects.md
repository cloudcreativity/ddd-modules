# Value Objects

Value objects help encapsulate the business logic and uniqueness of your domain.

These are objects that (unlike entities) have no identity and are always immutable - meaning their state cannot change
after they have been constructed. They are used to describe the characteristics of an entity, and to define data types
specific to the domain that are not represented by primitives.

This package provides no specific tooling for value objects, because they should be written according to the logic
of your domain. However, this chapter provides some examples and guidance on best practices.

## Examples

:::tip
PHP 8 `readonly public` properties are great for the value object use-case. They ensure immutability, while removing
the need to write lots of boilerplate "getter" methods.
:::

### Scalars

One use of a value object is to wrap a scalar value, to enforce business logic. For example, an `EmailAddress` value
object could be used to ensure that a string is always a valid email address.

```php
namespace App\Modules\EventManagement\BoundedContext\Domain\ValueObjects;

use CloudCreativity\Modules\Toolkit\Contracts;

final readonly class EmailAddress
{
    public function __construct(public string $value)
    {
        Contracts::assert(
            filter_var($this->value, FILTER_VALIDATE_EMAIL) !== false,
            'Invalid email address.',
        );
    }
}
```

### Multiple Values

It is also possible for value objects to represent the combination of multiple values.

In the example from the previous chapter, the `Attendee` aggregate root has a `Customer` value object.
If the business logic is that a customer is defined by the combination of their first name, last name and email address,
then the `Customer` value object would be defined as follows:

```php
namespace App\Modules\EventManagement\BoundedContext\Domain\ValueObjects;

final readonly class Customer
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public EmailAddress $this->value,
    ) {
    }
}
```

:::warning
It can be tempting to define value objects in a generic namespace or package, that can be required by multiple different
bounded contexts. For example, defining a customer value object and sharing it across multiple bounded contexts.

This should typically be avoided. That is because, in the example above, the customer value object defines the _nature_
of a customer specifically in the event management bounded context. Customer values could be different in other
bounded contexts, for example in an order delivery context, the customer value object might need to also hold an
address.
:::

### Enumerations

PHP 8 introduced enumerations, or _enums_ for short. These are perfect for domain value objects, as even the
[PHP docs describe](https://www.php.net/manual/en/language.enumerations.overview.php):

> Enumerations, or "Enums" allow a developer to define a custom type that is limited to one of a discrete number of
possible values. That can be especially helpful when defining a domain model, as it enables "making invalid states
unrepresentable."

Make good use of these. For example, if we needed to define the attendance status of an attendee:

```php
enum AttendanceStatus: string
{
    case Unconfirmed = 'unconfirmed';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
```

### Iterables

If you need to represent a collection of values, you can use a value object to encapsulate the collection and enforce
business logic. For example, a `ListOfTickets` value object could be used to ensure that a collection only contains
ticket entities, and that these are keyed as a zero-indexed list.

Having specific value object classes for these kinds of collections can be useful, as it allows you to encapsulate
business logic specific to the collection.

For example:

```php
namespace App\Modules\EventManagement\BoundedContext\Domain;

use CloudCreativity\Modules\Toolkit\Iterables\ListInterface;
use CloudCreativity\Modules\Toolkit\Iterables\ListTrait;

/**
 * @implements ListInterface<Ticket>
 */
class ListOfTickets implements ListInterface
{
    /** @use ListTrait<Ticket> */
    use ListTrait;

    public function __construct(Ticket ...$tickets)
    {
        $this->stack = $tickets;
    }

    public function allCancelled(): bool
    {
        foreach ($this->tickets as $ticket) {
            if ($ticket->isNotCancelled()) {
                return false;
            }
        }

        return true;
    }
}
```

:::tip
See the [Iterables Toolkit Chapter](../toolkit/iterables) for an explanation of the tooling provided to help you write
iterable value objects.
:::

## Invariants

Value objects should enforce invariants. This means that they should always be in a valid state, and should throw an
exception if they are ever constructed with invalid data.

For example, our `Customer` value object ensures that a customer always has a first name, last name and email
address. If it is successfully constructed, then this should always be true.

However, there is a flaw in the example given. Both the first and last name are type-hinted as a PHP `string`. This
means that they can be constructed with an empty string, which is not valid. The constructor should therefore
enforce this invariant:

```php
use CloudCreativity\Modules\Toolkit\Contracts;

final readonly class Customer
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public EmailAddress $email,
    ) {
        Contracts::assert(!empty($this->firstName), 'First name cannot be empty.');
        Contracts::assert(!empty($this->lastName), 'Last name cannot be empty.');
    }
}
```

:::tip
See the [Assertions Chapter](../toolkit/assertions) for an explanation of the `Contracts::assert()` helper.
:::

## Immutability

Value objects must always be immutable. This means that once they have been constructed, their state cannot change.
They can then be freely copied and shared, without fear of the consistency or state of the domain being
accidentally altered.

If there are scenarios where you want to provide a way to mutate the state of a value object via a method, you must
always ensure the method returns a new instance - with the original instance unaltered.

For example, let's say our business logic allowed for a customer's email to be changed. This would be incorrect:

```php
final readonly class Customer
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public EmailAddress $email,
    ) {
    }

    public function setEmail(EmailAddress $email): self
    {
        $this->email = $email;

        return $this;
    }
}
```

Here the `setEmail` method changes the email value on the current instance of the `Customer` value object, mutating
the state and breaking immutability. Instead, the method should return a new instance:

```php
final readonly class Customer
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public EmailAddress $email,
    ) {
    }

    public function withEmail(EmailAddress $email): self
    {
        return new self(
            firstName: $this->firstName,
            lastName: $this->lastName,
            email: $email,
        );
    }
}
```

:::tip
What you call your methods on your value objects is up to you. Here we've used `withEmail()` intentionally as a
convention to indicate that the method returns a new instance. As opposed to `setEmail()` which could imply the
email will be set (overwritten) on the current instance.

This convention isn't compulsory. However, ensuring you consistently name your methods across all value objects will
make your codebase predictable and easier to understand.
:::

### DateTimes

PHP provides a `DateTime` class, but it is not immutable. You should **never** use this, because it breaks the
immutability principal of value objects.

Instead, use PHP's `DateTimeImmutable` class, which does guarantee immutability.

If you are using a package that builds on these native classes, be careful about immutability.
For example, [the popular Carbon package](https://carbon.nesbot.com/) provides both `Carbon` and `CarbonImmutable`
classes. You should only ever use `CarbonImmutable`.

:::warning
Also watch out for using `DateTimeInterface`. If you type-hint a value as this, you cannot be sure that the value you
are given is immutable. If you must use the interface, immediately cast the value using
`DateTimeImmutable::createFromInterface()` to ensure you definitely have an immutable date-time:

```php
public function __construct(DateTimeInterface $createdAt)
{
    $this->createdAt = DateTimeImmutable::createFromInterface($createdAt);
}
```
:::

### Generic Collections

Some PHP frameworks provide "generic" classes for handling arrays, such as
[Laravel's Collection.](https://laravel.com/docs/collections)

These are typically mutable, and therefore should not be used to represent state in your domain. Even if they were
immutable, they are not specific to your domain - and add uncertainty to your codebase by potentially holding
_anything_.

Instead, you should use immutable value objects to represent collections that hold _specific_ data - such as the
`ListOfTickets` example given earlier. These are advantageous, because you can use them to encapsulate business logic
about that specific collection of data.

## Equality

Value objects must always have object equality. This means that two value objects are considered equal if they have the
same values, even if they are different instances.

In PHP, a good tip is to put the equality logic in an `equals()` method. This gives you a fluent interface for checking
whether two values are equally. Additionally, you can then use PHPUnit's `assertObjectEquals()` assertion to ensure
two value objects are equal in tests - which is a nice benefit.

For example, we can improve the example customer value object as follows:

```php
final readonly class Customer
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public EmailAddress $email,
    ) {
    }

    public function equals(self $other): bool
    {
        return $this->firstName === $other->firstName &&
            $this->lastName === $other->lastName &&
            $this->email->equals($other->email);
    }
}
```

## Serialization

It can be tempting to implement PHP's `JsonSerializable` or `Stringable` interfaces on value objects, so that they can
be easily serialized to JSON or strings. However, this is not recommended.

The reason is that when you implement either on a value object, you have no context to understand _why_ the object is
being serialized, and _how_ it should be serialized.

This can be illustrated with our example `Customer` value object. How should it be represented in JSON? Like this:

```json
{
  "first_name": "Frankie",
  "last_name": "Manning",
  "email": "frankie.manning@example.com"
}
```

Or like this?

```json
{
  "name": {
    "first": "Frankie",
    "last": "Manning"
  },
  "email": "frankie.manning@example.com"
}
```

Only the _presentation_ layer knows - as JSON is a data delivery mechanism. As we know, the domain layer is the
inner-most layer, that should have no knowledge of the outer layers - including the presentation layer. The same
applies for serializing to strings - this is a concern of the presentation layer.

The only exception to this would be _scalar_ value objects - for example our `EmailAddress` value object. In this case,
it is reasonable to implement `JsonSerializable` as the only possible representation of the value in JSON is
its underlying scalar value - in this case, a string.

:::info
The logic for this exception to the rule matches how PHP handles `BackedEnum`s - which in JSON are serialized to their
backing scalar value (either a string or integer).
:::

Where the scalar value object is also a string, it is also reasonable to implement `Stringable`.

So our `EmailAddress` class could look like this:

```php
namespace App\Modules\EventManagement\BoundedContext\Domain\ValueObjects;

use CloudCreativity\Modules\Toolkit\Contracts;

final readonly class EmailAddress implements \Stringable, \JsonSerializable
{
    public function __construct(public string $value)
    {
        Contracts::assert(
            filter_var($this->value, FILTER_VALIDATE_EMAIL) !== false,
            'Invalid email address.',
        );
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
```

:::tip
As always, consistency in your approach is key. If you decide to implement `JsonSerializable` or `Stringable` on your
scalar value objects, ensure you do so consistently across your domain. Predictability for developers is worth its
weight in gold!
:::

## Testing

Value objects should not be mocked in tests. This is because they enforce invariants, ensuring they can never be
incorrectly constructed. Mocking them breaks this rule, as a mock could be configured with invalid values.

Luckily, if you write your value objects like the examples in this chapter, you'll find that you cannot mock them
in unit tests. Declaring a class `final` and/or `readonly` both prevent a class from being mocked.
