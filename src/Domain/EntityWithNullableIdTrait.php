<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
