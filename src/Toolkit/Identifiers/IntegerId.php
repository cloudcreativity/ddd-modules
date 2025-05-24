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
    /**
     * @param Identifier|int $value
     * @return self
     */
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

    /**
     * IntegerId constructor.
     *
     * @param int $value
     */
    public function __construct(public int $value)
    {
        Contracts::assert($this->value > 0, 'Identifier value must be greater than zero.');
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return (string) $this->value;
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
     * @param IntegerId $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * @inheritDoc
     */
    public function key(): int
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function context(): int
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
