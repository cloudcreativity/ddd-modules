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
use JsonSerializable;
use Ramsey\Uuid\Uuid as BaseUuid;
use Ramsey\Uuid\UuidInterface;

final readonly class Uuid implements IdentifierInterface, JsonSerializable
{
    /**
     * @param IdentifierInterface|UuidInterface $value
     * @return self
     */
    public static function from(IdentifierInterface|UuidInterface $value): self
    {
        return match(true) {
            $value instanceof self => $value,
            $value instanceof UuidInterface => new self($value),
            default => throw new ContractException(
                'Unexpected identifier type, received: ' . get_debug_type($value),
            ),
        };
    }

    /**
     * Generate a random UUID, useful in tests.
     *
     * @return self
     */
    public static function random(): self
    {
        return new self(BaseUuid::uuid4());
    }

    /**
     * Uuid constructor.
     *
     * @param UuidInterface $value
     */
    public function __construct(public UuidInterface $value)
    {
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
        return $this->value->toString();
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
     * @param Uuid $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }

    /**
     * @inheritDoc
     */
    public function key(): string
    {
        return $this->value->toString();
    }

    /**
     * @inheritDoc
     */
    public function context(): string
    {
        return $this->value->toString();
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): string
    {
        return $this->value->jsonSerialize();
    }
}