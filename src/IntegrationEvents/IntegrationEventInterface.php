<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\IntegrationEvents;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

interface IntegrationEventInterface
{
    /**
     * @return UuidInterface
     */
    public function uuid(): UuidInterface;

    /**
     * @return DateTimeImmutable
     */
    public function occurredAt(): DateTimeImmutable;
}