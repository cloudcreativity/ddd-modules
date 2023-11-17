<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

namespace CloudCreativity\BalancedEvent\Common\Infrastructure\Queue\Middleware;

use Closure;
use CloudCreativity\BalancedEvent\Common\Infrastructure\Queue\QueueableInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\ModuleBasename;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogPushedToQueue implements QueueMiddlewareInterface
{
    /**
     * LogMessagePushedToQueue constructor.
     *
     * @param LoggerInterface $log
     * @param string $queueLevel
     * @param string $queuedLevel
     */
    public function __construct(
        private readonly LoggerInterface $log,
        private readonly string $queueLevel = LogLevel::DEBUG,
        private readonly string $queuedLevel = LogLevel::INFO,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(QueueableInterface $queueable, Closure $next): void
    {
        $name = ModuleBasename::tryFrom($queueable)?->toString() ?? $queueable::class;
        $context = $queueable->context();

        $this->log->log($this->queueLevel, "Queuing message {$name}.", $context);

        $next($queueable);

        $this->log->log($this->queuedLevel, "Queued message {$name}.", $context);
    }
}
