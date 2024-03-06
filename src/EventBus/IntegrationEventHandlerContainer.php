<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
