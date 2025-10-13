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

final readonly class StringId implements Identifier, JsonSerializable
{
    use IsIdentifier;

    public static function from(Identifier|string|null $value): self
    {
        return match(true) {
            $value instanceof self => $value,
            is_string($value) => new self($value),
            default => throw new ContractException(
                'Unexpected identifier type, received: ' . get_debug_type($value),
            ),
        };
    }

    public static function tryFrom(Identifier|string|null $value): ?self
    {
        return match(true) {
            $value instanceof self => $value,
            is_string($value) => new self($value),
            default => null,
        };
    }

    public function __construct(public string $value)
    {
        Contracts::assert(
            !empty($this->value) || '0' === $this->value,
            'Identifier value must be a non-empty string.',
        );
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value;
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

    public function key(): string
    {
        return $this->value;
    }

    public function context(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
