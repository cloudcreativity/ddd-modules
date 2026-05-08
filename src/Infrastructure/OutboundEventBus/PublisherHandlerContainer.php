<?php

/*
 * Copyright 2026 Cloud Creativity Limited
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
use CloudCreativity\Modules\Contracts\Messaging\IntegrationEvent;
use CloudCreativity\Modules\Infrastructure\InfrastructureException;
use Psr\Container\ContainerInterface;

final class PublisherHandlerContainer implements IPublisherHandlerContainer
{
    /**
     * @var array<class-string<IntegrationEvent>, Closure|string>
     */
    private array $bindings = [];

    public function __construct(
        private Closure|string|null $default = null,
        private readonly ?ContainerInterface $container = null,
    ) {
        if (is_string($this->default) && $this->container === null) {
            throw new InfrastructureException(
                'Cannot bind default event publisher handler as a string without a PSR container.',
            );
        }
    }

    /**
     * Bind a handler factory into the container.
     *
     * @param class-string<IntegrationEvent> $eventName
     */
    public function bind(string $eventName, Closure|string $binding): void
    {
        if (is_string($binding) && $this->container === null) {
            throw new InfrastructureException(
                'Cannot bind event publisher handler as a string without a PSR container.',
            );
        }

        $this->bindings[$eventName] = $binding;
    }

    public function withDefault(Closure|string $binding): void
    {
        if ($this->default !== null) {
            throw new InfrastructureException('Default event publisher handler is already set.');
        }

        if (is_string($binding) && $this->container === null) {
            throw new InfrastructureException(
                'Cannot bind default event publisher handler as a string without a PSR container.',
            );
        }

        $this->default = $binding;
    }

    public function get(string $eventName): PublisherHandler
    {
        $binding = $this->bindings[$eventName] ?? $this->default;

        if ($binding instanceof Closure) {
            $instance = $binding();
            assert(is_object($instance), "Handler binding for integration event {$eventName} must return an object.");
            return new PublisherHandler($instance);
        }

        if (is_string($binding)) {
            $instance = $this->container?->get($binding);
            assert(is_object($instance), "PSR container event publisher handler binding {$binding} is not an object.");
            return new PublisherHandler($instance);
        }

        throw new InfrastructureException('No handler bound for integration event: ' . $eventName);
    }
}
