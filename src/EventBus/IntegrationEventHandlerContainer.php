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

namespace CloudCreativity\Modules\EventBus;

use Closure;
use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;
use RuntimeException;

final class IntegrationEventHandlerContainer implements IntegrationEventHandlerContainerInterface
{
    /**
     * @var array<string, Closure>
     */
    private array $bindings = [];

    /**
     * @var array<string, Closure>
     */
    private array $handlers = [];

    /**
     * Bind a handler factory into the container.
     *
     * @param class-string<IntegrationEventInterface> $eventName
     * @param Closure $binding
     * @return void
     */
    public function bind(string $eventName, Closure $binding): void
    {
        $this->bindings[$eventName] = $binding;
    }

    /**
     * Register a handler.
     *
     * @param class-string<IntegrationEventInterface> $eventName
     * @param Closure $handler
     * @return void
     */
    public function register(string $eventName, Closure $handler): void
    {
        $this->handlers[$eventName] = $handler;
    }

    /**
     * @inheritDoc
     */
    public function get(string $eventName): IntegrationEventHandlerInterface
    {
        if ($handler = $this->handlers[$eventName] ?? null) {
            return new IntegrationEventHandler($handler);
        }

        $factory = $this->bindings[$eventName] ?? null;

        if ($factory) {
            $handler = $factory();
            assert(is_object($handler), "Handler binding for integration event {$eventName} must return an object.");
            return new IntegrationEventHandler($handler);
        }

        throw new RuntimeException('No handler bound for integration event: ' . $eventName);
    }
}
