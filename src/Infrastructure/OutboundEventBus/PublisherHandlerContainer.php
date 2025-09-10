<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\OutboundEventBus;

use Closure;
use CloudCreativity\Modules\Contracts\Infrastructure\OutboundEventBus\{
    PublisherHandlerContainer as IPublisherHandlerContainer};
use CloudCreativity\Modules\Contracts\Toolkit\Messages\IntegrationEvent;
use CloudCreativity\Modules\Infrastructure\InfrastructureException;

final class PublisherHandlerContainer implements IPublisherHandlerContainer
{
    /**
     * @var array<string, Closure>
     */
    private array $bindings = [];

    public function __construct(private readonly ?Closure $default = null)
    {
    }

    /**
     * Bind a handler factory into the container.
     *
     * @param class-string<IntegrationEvent> $eventName
     */
    public function bind(string $eventName, Closure $binding): void
    {
        $this->bindings[$eventName] = $binding;
    }

    public function get(string $eventName): PublisherHandler
    {
        $factory = $this->bindings[$eventName] ?? $this->default;

        if ($factory) {
            $handler = $factory();
            assert(is_object($handler), "Handler binding for integration event {$eventName} must return an object.");
            return new PublisherHandler($handler);
        }

        throw new InfrastructureException('No handler bound for integration event: ' . $eventName);
    }
}
