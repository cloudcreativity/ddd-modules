<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Infrastructure\Queue;

use CloudCreativity\BalancedEvent\Common\Infrastructure\Queue\QueueableBatch;
use CloudCreativity\BalancedEvent\Common\Infrastructure\Queue\QueueableInterface;
use CloudCreativity\BalancedEvent\Common\Infrastructure\Queue\QueuesBatches;
use CloudCreativity\BalancedEvent\Common\Infrastructure\Queue\QueueThroughMiddleware;

class TestQueueHandler implements QueuesBatches, QueueThroughMiddleware
{
    /**
     * @inheritDoc
     */
    public function withBatch(QueueableBatch $batch): void
    {
        // no-op
    }

    /**
     * @param QueueableInterface $job
     * @return void
     */
    public function queue(QueueableInterface $job): void
    {
        // no-op
    }

    /**
     * @inheritDoc
     */
    public function middleware(): array
    {
        return [];
    }
}
