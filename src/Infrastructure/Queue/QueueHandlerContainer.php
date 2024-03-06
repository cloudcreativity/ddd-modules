<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Queue;

use Closure;
use CloudCreativity\Modules\Infrastructure\InfrastructureException;

final class QueueHandlerContainer implements QueueHandlerContainerInterface
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
