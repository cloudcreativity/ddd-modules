<?php

/*
 * Copyright 2024 Cloud Creativity Limited
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

final class StringId implements Identifier, JsonSerializable
{
    /**
     * @param Identifier|string $value
     * @return self
     */
    public static function from(Identifier|string $value): self
    {
        return match(true) {
            $value instanceof self => $value,
            is_string($value) => new self($value),
            default => throw new ContractException(
                'Unexpected identifier type, received: ' . get_debug_type($value),
            ),
        };
    }

    /**
     * StringId constructor.
     *
     * @param string $value
     */
    public function __construct(public readonly string $value)
    {
        Contracts::assert(
            !empty($this->value) || '0' === $this->value,
            'Identifier value must be a non-empty string.',
        );
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function is(?Identifier $other): bool
    {
        if ($other instanceof self) {
            return $this->equals($other);
        }

        return false;
    }

    /**
     * @param StringId $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * @inheritDoc
     */
    public function key(): string
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function context(): string
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
