<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

namespace CloudCreativity\Modules\Infrastructure\DomainEventDispatching;

use Closure;
use RuntimeException;

final class ListenerContainer implements ListenerContainerInterface
{
    /**
     * @var array<string,Closure>
     */
    private array $bindings = [];

    /**
     * Bind a listener factory into the container.
     *
     * @param string $listenerName
     * @param Closure $binding
     * @return void
     */
    public function bind(string $listenerName, Closure $binding): void
    {
        $this->bindings[$listenerName] = $binding;
    }

    /**
     * @inheritDoc
     */
    public function get(string $listenerName): object
    {
        $factory = $this->bindings[$listenerName] ?? null;

        if ($factory) {
            $listener = $factory();
            assert(is_object($listener), "Listener binding for {$listenerName} must return an object.");
            return $listener;
        }

        throw new RuntimeException('Unrecognised listener name: ' . $listenerName);
    }
}
