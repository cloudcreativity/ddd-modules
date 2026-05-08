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
use CloudCreativity\Modules\Contracts\Bus\EventHandlerContainer as IEventHandlerContainer;
use CloudCreativity\Modules\Contracts\Messaging\IntegrationEvent;
use Psr\Container\ContainerInterface;

final class EventHandlerContainer implements IEventHandlerContainer
{
    /**
     * @var array<class-string<IntegrationEvent>, Closure|non-empty-string>
     */
    private array $bindings = [];

    /**
     * @param (Closure(): object)|non-empty-string|null $default
     */
    public function __construct(
        private Closure|string|null $default = null,
        private readonly ?ContainerInterface $container = null,
    ) {
    }

    /**
     * Bind a handler factory into the container.
     *
     * @param class-string<IntegrationEvent> $eventName
     * @param (Closure(): object)|non-empty-string $binding
     */
    public function bind(string $eventName, Closure|string $binding): void
    {
        if (is_string($binding) && $this->container === null) {
            throw new BusException('Cannot use a string event handler binding without a PSR container.');
        }

        $this->bindings[$eventName] = $binding;
    }

    /**
     * Bind a default handler factory into the container.
     *
     * @param (Closure(): object)|non-empty-string $binding
     */
    public function withDefault(Closure|string $binding): void
    {
        if ($this->default === null) {
            $this->default = $binding;
            return;
        }

        throw new BusException('Default event handler binding is already set.');
    }

    public function get(string $eventName): EventHandler
    {
        $binding = $this->bindings[$eventName] ?? $this->default;

        if ($binding instanceof Closure) {
            $instance = $binding();
            assert(is_object($instance), "Handler binding for integration event {$eventName} must return an object.");
            return new EventHandler($instance);
        }

        if (is_string($binding)) {
            $instance = $this->container?->get($binding);
            assert(is_object($instance), "PSR container event handler binding {$binding} is not an object.");
            return new EventHandler($instance);
        }

        throw new BusException('No handler bound for integration event: ' . $eventName);
    }
}
