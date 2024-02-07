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

namespace CloudCreativity\Modules\Infrastructure\EventBus\Middleware;

use Closure;
use CloudCreativity\Modules\Infrastructure\EventBus\IntegrationEventInterface;
use CloudCreativity\Modules\Infrastructure\Log\ObjectContext;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class LogInboundIntegrationEvent implements IntegrationEventMiddlewareInterface
{
    /**
     * LogInboundIntegrationEvent constructor.
     *
     * @param LoggerInterface $log
     * @param string $publishLevel
     * @param string $publishedLevel
     */
    public function __construct(
        private readonly LoggerInterface $log,
        private readonly string $publishLevel = LogLevel::DEBUG,
        private readonly string $publishedLevel = LogLevel::INFO,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(IntegrationEventInterface $event, Closure $next): void
    {
        $name = ModuleBasename::tryFrom($event)?->toString() ?? $event::class;

        $this->log->log(
            $this->publishLevel,
            "Receiving integration event {$name}.",
            $context = ObjectContext::from($event)->context(),
        );

        $next($event);

        $this->log->log($this->publishedLevel, "Received integration event {$name}.", $context);
    }
}
