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

use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\IdentifierInterface;

trait EntityTrait
{
    /**
     * @var IdentifierInterface
     */
    private IdentifierInterface $id;

    /**
     * @inheritDoc
     */
    public function getId(): ?IdentifierInterface
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function is(?EntityInterface $other): bool
    {
        if ($other instanceof $this) {
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
