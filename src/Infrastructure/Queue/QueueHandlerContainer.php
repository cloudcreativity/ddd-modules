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

namespace CloudCreativity\BalancedEvent\Common\Infrastructure\Queue;

use Closure;
use CloudCreativity\BalancedEvent\Common\Infrastructure\InfrastructureException;

class QueueHandlerContainer implements QueueHandlerContainerInterface
{
    /**
     * @var array<string,Closure>
     */
    private array $bindings = [];

    /**
     * @var array<string,Closure>
     */
    private array $handlers = [];

    /**
     * Bind a queue handler factory into the container.
     *
     * @param string $queueableName
     * @param Closure $binding
     * @return void
     */
    public function bind(string $queueableName, Closure $binding): void
    {
        $this->bindings[$queueableName] = $binding;
    }

    /**
     * Register a queue handler.
     *
     * @param string $queueableName
     * @param Closure $handler
     * @return void
     */
    public function register(string $queueableName, Closure $handler): void
    {
        $this->handlers[$queueableName] = $handler;
    }

    /**
     * @inheritDoc
     */
    public function get(string $queueableName): QueueHandlerInterface
    {
        if ($handler = $this->handlers[$queueableName] ?? null) {
            return new QueueHandler($handler);
        }

        $factory = $this->bindings[$queueableName] ?? null;

        if ($factory) {
            $innerHandler = $factory();
            assert(is_object($innerHandler), "Queue handler binding for {$queueableName} must return an object.");
            return new QueueHandler($innerHandler);
        }

        throw new InfrastructureException('No queue handler bound for queueable class: ' . $queueableName);
    }
}
