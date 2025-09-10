<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\DomainEventDispatching\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Application\DomainEventDispatching\DomainEventMiddleware;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final readonly class LogDomainEventDispatch implements DomainEventMiddleware
{
    /**
     * LogDomainEventDispatch constructor
     *
     */
    public function __construct(
        private LoggerInterface $logger,
        private string $dispatchLevel = LogLevel::DEBUG,
        private string $dispatchedLevel = LogLevel::INFO,
    ) {
    }

    public function __invoke(DomainEvent $event, Closure $next): void
    {
        $name = ModuleBasename::tryFrom($event)?->toString() ?? $event::class;

        $this->logger->log($this->dispatchLevel, "Dispatching domain event {$name}.");

        $next($event);

        $this->logger->log($this->dispatchedLevel, "Dispatched domain event {$name}.");
    }
}
