<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus;

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
