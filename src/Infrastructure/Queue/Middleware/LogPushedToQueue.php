<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
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
