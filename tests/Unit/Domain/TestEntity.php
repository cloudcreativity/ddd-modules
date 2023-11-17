<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Domain;

use CloudCreativity\BalancedEvent\Common\Domain\EntityInterface;
use CloudCreativity\BalancedEvent\Common\Domain\EntityTrait;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\IdentifierInterface;

class TestEntity implements EntityInterface
{
    use EntityTrait;

    /**
     * TestEntity constructor
     *
     * @param IdentifierInterface $id
     */
    public function __construct(IdentifierInterface $id)
    {
        $this->id = $id;
    }
}
