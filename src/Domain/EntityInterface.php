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

interface EntityInterface
{
    /**
     * Get the entity's identifier.
     *
     * @return IdentifierInterface|null
     */
    public function getId(): ?IdentifierInterface;

    /**
     * Is this entity the same as the provided entity?
     *
     * @param EntityInterface|null $other
     * @return bool
     */
    public function is(?EntityInterface $other): bool;

    /**
     * Is this entity not the same as the provided entity?
     *
     * @param EntityInterface|null $other
     * @return bool
     */
    public function isNot(?EntityInterface $other): bool;
}
