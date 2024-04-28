<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Ports\Driving\Commands;

use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

interface CommandDispatcherInterface
{
    /**
     * Dispatch the given command.
     *
     * @param CommandInterface $command
     * @return ResultInterface<mixed>
     */
    public function dispatch(CommandInterface $command): ResultInterface;

    /**
     * Queue a command for asynchronous dispatching.
     *
     * @param CommandInterface $command
     * @return void
     */
    public function queue(CommandInterface $command): void;
}
