<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Identifiers;

use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Toolkit\ContractException;
use CloudCreativity\Modules\Toolkit\Contracts;
use JsonSerializable;

final readonly class IntegerId implements Identifier, JsonSerializable
{
    use IsIdentifier;

    public static function from(Identifier|int $value): self
    {
        return match(true) {
            $value instanceof self => $value,
            is_int($value) => new self($value),
            default => throw new ContractException(
                'Unexpected identifier type, received: ' . get_debug_type($value),
            ),
        };
    }

    public function __construct(public int $value)
    {
        Contracts::assert($this->value > 0, 'Identifier value must be greater than zero.');
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return (string) $this->value;
    }

    public function is(?Identifier $other): bool
    {
        if ($other instanceof self) {
            return $this->equals($other);
        }

        return false;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function key(): int
    {
        return $this->value;
    }

    public function context(): int
    {
        return $this->value;
    }

    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
