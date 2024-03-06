<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Queue;

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
