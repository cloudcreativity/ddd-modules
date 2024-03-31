<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus\Queue;

use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;

interface CommandEnqueuerInterface
{
    /**
     * Add the command to the queue.
     *
     * @param CommandInterface $command
     * @return void
     */
    public function queue(CommandInterface $command): void;
}
