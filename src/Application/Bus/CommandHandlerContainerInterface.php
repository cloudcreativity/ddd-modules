<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus;

interface CommandHandlerContainerInterface
{
    /**
     * Get a command handler for the provided command name.
     *
     * @param string $commandClass
     * @return CommandHandlerInterface
     */
    public function get(string $commandClass): CommandHandlerInterface;
}