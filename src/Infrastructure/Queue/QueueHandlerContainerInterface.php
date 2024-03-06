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

interface QueueHandlerContainerInterface
{
    /**
     * Get a queue handler for the specified message.
     *
     * @param string $queueableName
     * @return QueueHandlerInterface
     */
    public function get(string $queueableName): QueueHandlerInterface;
}
