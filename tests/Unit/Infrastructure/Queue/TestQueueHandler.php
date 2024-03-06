<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\Queue;

use CloudCreativity\Modules\Infrastructure\Queue\QueueableBatch;
use CloudCreativity\Modules\Infrastructure\Queue\QueueableInterface;
use CloudCreativity\Modules\Infrastructure\Queue\QueuesBatches;
use CloudCreativity\Modules\Infrastructure\Queue\QueueThroughMiddleware;

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
