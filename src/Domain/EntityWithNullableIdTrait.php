<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Domain;

use CloudCreativity\BalancedEvent\Common\Toolkit\Contracts;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\IdentifierInterface;

trait EntityWithNullableIdTrait
{
    /**
     * @var IdentifierInterface|null
     */
    private ?IdentifierInterface $id = null;

    /**
     * @inheritDoc
     */
    public function getId(): ?IdentifierInterface
    {
        return $this->id;
    }

    /**
     * @return IdentifierInterface
     */
    public function getIdOrFail(): IdentifierInterface
    {
        assert($this->id instanceof IdentifierInterface, 'Entity does not have an identifier.');

        return $this->id;
    }

    /**
     * @return bool
     */
    public function hasId(): bool
    {
        return $this->id instanceof IdentifierInterface;
    }

    /**
     * @param IdentifierInterface $id
     * @return $this
     */
    public function setId(IdentifierInterface $id): static
    {
        Contracts::assert(null === $this->id, 'Cannot set identity as entity already has an identifier.');

        $this->id = $id;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function is(?EntityInterface $other): bool
    {
        if ($other instanceof $this && $this->id) {
            return $this->id->is(
                $other->getId(),
            );
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isNot(?EntityInterface $other): bool
    {
        return !$this->is($other);
    }
}
