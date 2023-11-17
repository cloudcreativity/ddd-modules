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

use CloudCreativity\BalancedEvent\Common\Toolkit\ContractException;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\IdentifierInterface;

final readonly class IdentifierOrEntity
{
    /**
     * @var IdentifierInterface|null
     */
    public ?IdentifierInterface $id;

    /**
     * @var EntityInterface|null
     */
    public ?EntityInterface $entity;

    /**
     * @param IdentifierInterface|EntityInterface $idOrEntity
     * @return self
     */
    public static function make(IdentifierInterface|EntityInterface $idOrEntity): self
    {
        return new self($idOrEntity);
    }

    /**
     * IdentifierOrEntity constructor.
     *
     * @param IdentifierInterface|EntityInterface $idOrEntity
     */
    public function __construct(IdentifierInterface|EntityInterface $idOrEntity)
    {
        $this->id = ($idOrEntity instanceof IdentifierInterface) ? $idOrEntity : null;
        $this->entity = ($idOrEntity instanceof EntityInterface) ? $idOrEntity : null;
    }

    /**
     * @return IdentifierInterface|null
     */
    public function id(): ?IdentifierInterface
    {
        if ($this->entity) {
            return $this->entity->getId();
        }

        return $this->id;
    }

    /**
     * @return IdentifierInterface
     */
    public function idOrFail(): IdentifierInterface
    {
        if ($id = $this->id()) {
            return $id;
        }

        throw new ContractException('Entity does not have an identifier.');
    }
}
