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
