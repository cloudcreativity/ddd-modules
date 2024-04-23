<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\OutboundEventBus;

use Closure;
use CloudCreativity\Modules\Infrastructure\InfrastructureException;
use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;

final class PublisherHandlerContainer implements PublisherHandlerContainerInterface
{
    /**
     * @var array<string, Closure>
     */
    private array $bindings = [];

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
     * @inheritDoc
     */
    public function get(string $eventName): PublisherHandlerInterface
    {
        $factory = $this->bindings[$eventName] ?? null;

        if ($factory) {
            $handler = $factory();
            assert(is_object($handler), "Handler binding for integration event {$eventName} must return an object.");
            return new PublisherHandler($handler);
        }

        throw new InfrastructureException('No handler bound for integration event: ' . $eventName);
    }
}
