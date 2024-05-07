<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Application\Ports\Driven\Queue;

use CloudCreativity\Modules\Contracts\Application\Messages\Command;

interface Queue
{
    /**
     * Push a command on to the queue.
     *
     * @param Command $command
     * @return void
     */
    public function push(Command $command): void;
}
