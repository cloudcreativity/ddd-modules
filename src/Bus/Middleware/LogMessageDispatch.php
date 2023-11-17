<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Bus\Middleware;

use Closure;
use CloudCreativity\BalancedEvent\Common\Bus\MessageInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ResultInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\ModuleBasename;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

readonly class LogMessageDispatch implements MessageMiddlewareInterface
{
    /**
     * LogMessageDispatch constructor.
     *
     * @param LoggerInterface $logger
     * @param string $dispatchLevel
     * @param string $dispatchedLevel
     */
    public function __construct(
        private LoggerInterface $logger,
        private string $dispatchLevel = LogLevel::DEBUG,
        private string $dispatchedLevel = LogLevel::INFO,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(MessageInterface $message, Closure $next): ResultInterface
    {
        $name = ModuleBasename::tryFrom($message)?->toString() ?? $message::class;

        $this->logger->log(
            $this->dispatchLevel,
            "Bus dispatching {$name}.",
            $message->context(),
        );

        /** @var ResultInterface $result */
        $result = $next($message);

        $this->logger->log(
            $this->dispatchedLevel,
            "Bus dispatched {$name}.",
            $result->context(),
        );

        return $result;
    }
}
