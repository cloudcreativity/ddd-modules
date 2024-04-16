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

interface QueueDispatcherInterface
{
    /**
     * Dispatch the given queue job.
     *
     * @param QueueJobInterface $queueable
     * @return ResultInterface<mixed>
     */
    public function dispatch(QueueJobInterface $queueable): ResultInterface;
}
