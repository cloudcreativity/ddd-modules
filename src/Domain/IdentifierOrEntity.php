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

use CloudCreativity\Modules\Toolkit\ContractException;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;

final class IdentifierOrEntity
{
    /**
     * @var IdentifierInterface|null
     */
    public readonly ?IdentifierInterface $id;

    /**
     * @var EntityInterface|null
     */
    public readonly ?EntityInterface $entity;

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
