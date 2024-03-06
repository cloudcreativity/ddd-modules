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

interface QueueHandlerInterface extends QueuesBatches, QueueThroughMiddleware
{
    /**
     * Queue the message.
     *
     * @param QueueableInterface $message
     * @return void
     */
    public function __invoke(QueueableInterface $message): void;
}
