<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Queue\Enqueuers;

use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;

interface EnqueuerInterface
{
    /**
     * Put the command or queue job on the queue.
     *
     * @param CommandInterface|QueueJobInterface $queueable
     * @return void
     */
    public function __invoke(CommandInterface|QueueJobInterface $queueable): void;
}
