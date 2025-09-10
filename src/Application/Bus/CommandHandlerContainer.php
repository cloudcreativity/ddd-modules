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

use Closure;
use CloudCreativity\Modules\Application\ApplicationException;
use CloudCreativity\Modules\Contracts\Application\Bus\CommandHandlerContainer as ICommandHandlerContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;

final class CommandHandlerContainer implements ICommandHandlerContainer
{
    /**
     * @var array<class-string<Command>, Closure>
     */
    private array $bindings = [];

    /**
     * Bind a command handler into the container.
     *
     * @param class-string<Command> $commandClass
     * @param Closure(): object $binding
     */
    public function bind(string $commandClass, Closure $binding): void
    {
        $this->bindings[$commandClass] = $binding;
    }

    public function get(string $commandClass): CommandHandler
    {
        $factory = $this->bindings[$commandClass] ?? null;

        if ($factory) {
            $innerHandler = $factory();
            assert(is_object($innerHandler), "Command handler binding for {$commandClass} must return an object.");
            return new CommandHandler($innerHandler);
        }

        throw new ApplicationException('No command handler bound for command class: ' . $commandClass);
    }
}
