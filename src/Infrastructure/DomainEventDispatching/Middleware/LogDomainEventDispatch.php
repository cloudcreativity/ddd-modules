<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Infrastructure\DomainEventDispatching\Middleware;

use Closure;
use CloudCreativity\BalancedEvent\Common\Domain\Events\DomainEventInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\ModuleBasename;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogDomainEventDispatch implements EventMiddlewareInterface
{
    /**
     * LogEventDispatch constructor
     *
     * @param LoggerInterface $logger
     * @param string $dispatchLevel
     * @param string $dispatchedLevel
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $dispatchLevel = LogLevel::DEBUG,
        private readonly string $dispatchedLevel = LogLevel::INFO,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(DomainEventInterface $event, Closure $next): void
    {
        $name = ModuleBasename::tryFrom($event)?->toString() ?? $event::class;

        $this->logger->log($this->dispatchLevel, "Dispatching event {$name}.");

        $next($event);

        $this->logger->log($this->dispatchedLevel, "Dispatched event {$name}.");
    }
}
