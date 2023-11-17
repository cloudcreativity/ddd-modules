<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Infrastructure\Queue;

interface QueueInterface
{
    /**
     * Push a message onto the queue.
     *
     * @param QueueableInterface $queueable
     * @return void
     */
    public function push(QueueableInterface $queueable): void;

    /**
     * Push a batch of messages onto the queue.
     *
     * @param QueueableBatch $batch
     * @return void
     */
    public function pushBatch(QueueableBatch $batch): void;
}
