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

interface EnqueuerInterface
{
    /**
     * Add the message to the queue.
     *
     * @param QueueableInterface $queueable
     * @return void
     */
    public function queue(QueueableInterface $queueable): void;
}
