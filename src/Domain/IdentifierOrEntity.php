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

use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Toolkit\ContractException;

final class IdentifierOrEntity
{
    /**
     * @var Identifier|null
     */
    public readonly ?Identifier $id;

    /**
     * @var EntityInterface|null
     */
    public readonly ?EntityInterface $entity;

    /**
     * @param Identifier|EntityInterface $idOrEntity
     * @return self
     */
    public static function make(Identifier|EntityInterface $idOrEntity): self
    {
        return new self($idOrEntity);
    }

    /**
     * IdentifierOrEntity constructor.
     *
     * @param Identifier|EntityInterface $idOrEntity
     */
    public function __construct(Identifier|EntityInterface $idOrEntity)
    {
        $this->id = ($idOrEntity instanceof Identifier) ? $idOrEntity : null;
        $this->entity = ($idOrEntity instanceof EntityInterface) ? $idOrEntity : null;
    }

    /**
     * @return Identifier|null
     */
    public function id(): ?Identifier
    {
        if ($this->entity) {
            return $this->entity->getId();
        }

        return $this->id;
    }

    /**
     * @return Identifier
     */
    public function idOrFail(): Identifier
    {
        if ($id = $this->id()) {
            return $id;
        }

        throw new ContractException('Entity does not have an identifier.');
    }
}
