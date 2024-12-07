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

use CloudCreativity\Modules\Contracts\Domain\Entity;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Toolkit\Contracts;

trait IsEntityWithNullableId
{
    /**
     * @var Identifier|null
     */
    private ?Identifier $id = null;

    /**
     * @inheritDoc
     */
    public function getId(): ?Identifier
    {
        return $this->id;
    }

    /**
     * @return Identifier
     */
    public function getIdOrFail(): Identifier
    {
        assert($this->id instanceof Identifier, 'Entity does not have an identifier.');

        return $this->id;
    }

    /**
     * @return bool
     */
    public function hasId(): bool
    {
        return $this->id instanceof Identifier;
    }

    /**
     * @param Identifier $id
     * @return $this
     */
    public function setId(Identifier $id): static
    {
        Contracts::assert(null === $this->id, 'Cannot set identity as entity already has an identifier.');

        $this->id = $id;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function is(?Entity $other): bool
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
    public function isNot(?Entity $other): bool
    {
        return !$this->is($other);
    }
}
