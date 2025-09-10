<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus;

use CloudCreativity\Modules\Contracts\Application\Ports\Driven\Queue;
use CloudCreativity\Modules\Contracts\Application\Ports\Driving\CommandQueuer as ICommandQueuer;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;

class CommandQueuer implements ICommandQueuer
{
    /**
     * CommandQueuer constructor.
     *
     */
    public function __construct(private readonly Queue $queue)
    {
    }

    public function queue(Command $command): void
    {
        $this->queue->push($command);
    }
}
