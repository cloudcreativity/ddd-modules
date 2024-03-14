# Assertions

Entities and aggregates should be designed to maintain invariants. An invariant is a condition that must always be true
for the entity or aggregate to be in a valid state. Assertions in your code are a way to enforce these invariants.

## Assertion Helper

This package provides a simple assertion helper that can be used to enforce invariants in your code. It is designed
so your code expresses what should be _correct_ about the state of your entities, aggregates and value objects - instead
of checking for the _incorrect_ state.

Here's an example to illustrate the point. Let's say you have an `Age` value object that should always be greater than
or equal to 18. You could write a method like this:

```php
final readonly class Age
{
    public function __construct(private int $value)
    {
        if ($this->value < 18) {
            throw new \InvalidArgumentException(
              'Age must be greater than or equal to 0',
            );
        }
    }
}
```

Here our code is expressing the _incorrect_ state - "age is less than 18" - when actually our value object is about
enforcing that "age is greater than or equal to 18". This is where the assertion helper comes in. We can rewrite
the `Age` value object like this:

```php
use CloudCreativity\Modules\Toolkit\Contracts;

final readonly class Age
{
    public function __construct(private int $value)
    {
        Contracts::assert(
            $this->value >= 18,
            'Age must be greater than or equal to 18',
        );
    }
}
```

The assertion helper throws if the provided check evaluates to `false`. It throws an instance of
`CloudCreativity\Modules\Toolkit\ContractException` - with the message set to the message provided to the helper.

## PHP's `assert()` Function

Why is the built-in `assert()` function not used to enforce invariants?

As [described by the PHP docs](https://www.php.net/manual/en/function.assert.php) this function is for debugging
purposes only. In production, uses of the `assert()` function are optimized out. This means that if you use `assert()`
to enforce invariants, they will not be enforced in production.

For example, this means that in production we could end up with an `Age` value object that holds an age less than 18.
This is not what we want - the `Age` value object should never be instantiated in this state.

There are some situations where it is ok to use the `assert()` function. Here's an example:

```php
class Ticket implements EntityInterface
{
    public function __construct(
        private readonly IdentifierInterface $id,
        private ?TicketStatusEnum $status = null
        // ...other properties
    ) {
    }

    public function getStatus(): TicketStatusEnum
    {
        assert(
            $this->status instanceof TicketStatusEnum,
            'Ticket status has not been set via a state change.',
        );

        return $this->status;
    }
}
```

In this example, a ticket can be instantiated without a status. However, we expect the status to be set before we
attempt to retrieve it.

Here we can safely use the assert function, because with the assertion optimized out in production, the getter would
still fail because it has a return type that is _not_ nullable.

The assertion here is useful for debugging purposes. In non-production environments where assertions are not optimized
out, the assertion message gives the developer a better description of what the problem is.

