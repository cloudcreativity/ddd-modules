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

use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;

interface QueueInterface
{
    /**
     * Push a command or commands onto the queue.
     *
     * @param CommandInterface|iterable<CommandInterface> $command
     * @return void
     */
    public function push(CommandInterface|iterable $command): void;
}
