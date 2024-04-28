<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Ports\Driven\Queue;

use CloudCreativity\Modules\Application\Messages\CommandInterface;

interface QueueInterface
{
    /**
     * Push a command on to the queue.
     *
     * @param CommandInterface $command
     * @return void
     */
    public function push(CommandInterface $command): void;
}
