<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers;

use CloudCreativity\BalancedEvent\Common\Toolkit\ContractException;
use CloudCreativity\BalancedEvent\Common\Toolkit\Contracts;
use JsonSerializable;

final readonly class IntegerId implements IdentifierInterface, JsonSerializable
{
    /**
     * @param IdentifierInterface|int $value
     * @return self
     */
    public static function from(IdentifierInterface|int $value): self
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
    public function is(?IdentifierInterface $other): bool
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