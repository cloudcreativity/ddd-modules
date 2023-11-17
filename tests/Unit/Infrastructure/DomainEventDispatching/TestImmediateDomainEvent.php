<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Infrastructure\DomainEventDispatching;

use CloudCreativity\BalancedEvent\Common\Domain\Events\DomainEventInterface;
use CloudCreativity\BalancedEvent\Common\Domain\Events\OccursImmediately;
use DateTimeImmutable;

class TestImmediateDomainEvent implements DomainEventInterface, OccursImmediately
{
    /**
     * @inheritDoc
     */
    public function context(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function occurredAt(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}