<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Domain;

use CloudCreativity\Modules\Contracts\Domain\Entity;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Toolkit\ContractException;

final readonly class IdentifierOrEntity
{
    public ?Identifier $id;

    public ?Entity $entity;

    public static function make(Entity|Identifier $idOrEntity): self
    {
        return new self($idOrEntity);
    }

    public function __construct(Entity|Identifier $idOrEntity)
    {
        $this->id = ($idOrEntity instanceof Identifier) ? $idOrEntity : null;
        $this->entity = ($idOrEntity instanceof Entity) ? $idOrEntity : null;
    }

    public function id(): ?Identifier
    {
        if ($this->entity) {
            return $this->entity->getId();
        }

        return $this->id;
    }

    public function idOrFail(): Identifier
    {
        if ($id = $this->id()) {
            return $id;
        }

        throw new ContractException('Entity does not have an identifier.');
    }
}
