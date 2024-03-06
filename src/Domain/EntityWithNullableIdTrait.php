<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Domain;

use CloudCreativity\Modules\Toolkit\Contracts;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;

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
