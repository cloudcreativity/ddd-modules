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

namespace CloudCreativity\Modules\EventBus\Outbound;

use Closure;
use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;
use RuntimeException;

final class PublisherContainer implements PublisherContainerInterface
{
    /**
     * @var array<string, Closure>
     */
    private array $bindings = [];

    /**
     * @var array<string, Closure>
     */
    private array $publishers = [];

    /**
     * Bind a publisher factory into the container.
     *
     * @param class-string<IntegrationEventInterface> $queueableName
     * @param Closure $binding
     * @return void
     */
    public function bind(string $queueableName, Closure $binding): void
    {
        $this->bindings[$queueableName] = $binding;
    }

    /**
     * Register a publisher.
     *
     * @param class-string<IntegrationEventInterface> $queueableName
     * @param Closure $handler
     * @return void
     */
    public function register(string $queueableName, Closure $handler): void
    {
        $this->publishers[$queueableName] = $handler;
    }

    /**
     * @inheritDoc
     */
    public function get(string $eventName): PublisherHandlerInterface
    {
        if ($publisher = $this->publishers[$eventName] ?? null) {
            return new PublisherHandler($publisher);
        }

        $factory = $this->bindings[$eventName] ?? null;

        if ($factory) {
            $publisher = $factory();
            assert(is_object($publisher), "Publisher binding for {$eventName} must return an object.");
            return new PublisherHandler($publisher);
        }

        throw new RuntimeException('No publisher bound for integration event: ' . $eventName);
    }
}
