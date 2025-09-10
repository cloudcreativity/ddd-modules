<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\InboundEventBus;

use Closure;
use CloudCreativity\Modules\Contracts\Application\InboundEventBus\EventHandlerContainer as IEventHandlerContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\IntegrationEvent;
use RuntimeException;

final class EventHandlerContainer implements IEventHandlerContainer
{
    /**
     * @var array<string, Closure>
     */
    private array $bindings = [];

    /**
     * EventHandlerContainer constructor.
     *
     * @param ?Closure(): object $default
     */
    public function __construct(private readonly ?Closure $default = null)
    {
    }

    /**
     * Bind a handler factory into the container.
     *
     * @param class-string<IntegrationEvent> $eventName
     * @param Closure(): object $binding
     */
    public function bind(string $eventName, Closure $binding): void
    {
        $this->bindings[$eventName] = $binding;
    }

    public function get(string $eventName): EventHandler
    {
        $factory = $this->bindings[$eventName] ?? $this->default;

        if ($factory) {
            $handler = $factory();
            assert(is_object($handler), "Handler binding for integration event {$eventName} must return an object.");
            return new EventHandler($handler);
        }

        throw new RuntimeException('No handler bound for integration event: ' . $eventName);
    }
}
