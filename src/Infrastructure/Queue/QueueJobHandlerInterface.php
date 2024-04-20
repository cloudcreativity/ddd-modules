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

use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

interface QueueJobHandlerInterface extends DispatchThroughMiddleware
{
    /**
     * Execute the queue job.
     *
     * @param QueueJobInterface $job
     * @return ResultInterface<mixed>
     */
    public function __invoke(QueueJobInterface $job): ResultInterface;
}
