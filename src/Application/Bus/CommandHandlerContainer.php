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

use Closure;
use RuntimeException;

final class CommandHandlerContainer implements CommandHandlerContainerInterface
{
    /**
     * @var array<string, Closure>
     */
    private array $bindings = [];

    /**
     * Bind a command handler into the container.
     *
     * @param string $commandClass
     * @param Closure $binding
     * @return void
     */
    public function bind(string $commandClass, Closure $binding): void
    {
        $this->bindings[$commandClass] = $binding;
    }

    /**
     * @inheritDoc
     */
    public function get(string $commandClass): CommandHandlerInterface
    {
        $factory = $this->bindings[$commandClass] ?? null;

        if ($factory) {
            $innerHandler = $factory();
            assert(is_object($innerHandler), "Command handler binding for {$commandClass} must return an object.");
            return new CommandHandler($innerHandler);
        }

        throw new RuntimeException('No command handler bound for command class: ' . $commandClass);
    }
}
