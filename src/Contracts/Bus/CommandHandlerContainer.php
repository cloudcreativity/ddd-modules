<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Bus;

use CloudCreativity\Modules\Contracts\Messaging\Command;

interface CommandHandlerContainer
{
    /**
     * Get a command handler for the provided command name.
     *
     * @param class-string<Command> $commandClass
     */
    public function get(string $commandClass): CommandHandler;
}
