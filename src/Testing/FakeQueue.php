<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Testing;

use CloudCreativity\Modules\Contracts\Application\Ports\Driven\Queue;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;
use LogicException;

class FakeQueue implements Queue
{
    /**
     * @var array<Command>
     */
    public array $commands = [];

    /**
     * @inheritDoc
     */
    public function push(Command $command): void
    {
        $this->commands[] = $command;
    }

    /**
     * Expect a single command to be queued and return it.
     *
     * @return Command
     */
    public function sole(): Command
    {
        if (count($this->commands) === 1) {
            return $this->commands[0];
        }

        throw new LogicException(sprintf(
            'Expected one command in the queue but there are %d commands.',
            count($this->commands),
        ));
    }
}
