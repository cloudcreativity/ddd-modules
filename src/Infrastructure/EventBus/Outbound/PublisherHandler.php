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

namespace CloudCreativity\Modules\Infrastructure\EventBus\Outbound;

use Closure;
use CloudCreativity\Modules\Infrastructure\EventBus\IntegrationEventInterface;
use CloudCreativity\Modules\Infrastructure\EventBus\PublishThroughMiddleware;

final class PublisherHandler implements PublisherHandlerInterface
{
    /**
     * PublisherHandler constructor.
     *
     * @param object $publisher
     */
    public function __construct(private readonly object $publisher)
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(IntegrationEventInterface $event): void
    {
        if ($this->publisher instanceof Closure) {
            ($this->publisher)($event);
            return;
        }

        assert(method_exists($this->publisher, 'publish'), sprintf(
            'Cannot publish "%s" - handler "%s" does not have a publish method.',
            $event::class,
            $this->publisher::class,
        ));

        $this->publisher->publish($event);
    }

    /**
     * @inheritDoc
     */
    public function middleware(): array
    {
        if ($this->publisher instanceof PublishThroughMiddleware) {
            return $this->publisher->middleware();
        }

        return [];
    }
}
