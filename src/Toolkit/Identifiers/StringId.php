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

final readonly class StringId implements IdentifierInterface, JsonSerializable
{
    /**
     * @param IdentifierInterface|string $value
     * @return self
     */
    public static function from(IdentifierInterface|string $value): self
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
    public function __construct(public string $value)
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
    public function is(?IdentifierInterface $other): bool
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