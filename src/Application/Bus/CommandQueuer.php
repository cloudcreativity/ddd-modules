<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus;

use CloudCreativity\Modules\Contracts\Application\Ports\Queue;
use CloudCreativity\Modules\Contracts\Bus\CommandQueuer as ICommandQueuer;
use CloudCreativity\Modules\Contracts\Messaging\Command;

abstract class CommandQueuer implements ICommandQueuer
{
    public function __construct(private readonly Queue $queue)
    {
    }

    public function queue(Command $command): void
    {
        $this->queue->push($command);
    }
}
