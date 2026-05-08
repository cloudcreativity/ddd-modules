<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus;

use Closure;
use CloudCreativity\Modules\Contracts\Bus\CommandHandlerContainer as ICommandHandlerContainer;
use CloudCreativity\Modules\Contracts\Messaging\Command;
use Psr\Container\ContainerInterface;

final class CommandHandlerContainer implements ICommandHandlerContainer
{
    /**
     * @var array<class-string<Command>, class-string|Closure>
     */
    private array $bindings = [];

    public function __construct(private readonly ?ContainerInterface $container = null)
    {
    }

    /**
     * Bind a command handler into the container.
     *
     * @param class-string<Command> $commandClass
     * @param class-string|(Closure(): object) $binding
     */
    public function bind(string $commandClass, Closure|string $binding): void
    {
        if (is_string($binding) && $this->container === null) {
            throw new BusException('Cannot use a string command handler binding without a PSR container.');
        }

        $this->bindings[$commandClass] = $binding;
    }

    public function get(string $commandClass): CommandHandler
    {
        $binding = $this->bindings[$commandClass] ?? null;

        if ($binding instanceof Closure) {
            $instance = $binding();
            assert(is_object($instance), "Command handler binding for {$commandClass} must return an object.");
            return new CommandHandler($instance);
        }

        if (is_string($binding)) {
            $instance = $this->container?->get($binding);
            assert(is_object($instance), "PSR container command handler binding {$binding} is not an object.");
            return new CommandHandler($instance);
        }

        throw new BusException('No command handler bound for command class: ' . $commandClass);
    }
}
