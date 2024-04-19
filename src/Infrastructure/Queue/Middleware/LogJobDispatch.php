<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Queue\Middleware;

use Closure;
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;
use CloudCreativity\Modules\Toolkit\Loggable\ObjectContext;
use CloudCreativity\Modules\Toolkit\Loggable\ResultContext;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class LogJobDispatch implements JobMiddlewareInterface
{
    /**
     * LogJobDispatch constructor.
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
    public function __invoke(QueueJobInterface $job, Closure $next): ResultInterface
    {
        $name = ModuleBasename::tryFrom($job)?->toString() ?? $job::class;

        $this->logger->log(
            $this->dispatchLevel,
            "Queue bus dispatching {$name}.",
            ObjectContext::from($job)->context(),
        );

        /** @var ResultInterface<mixed> $result */
        $result = $next($job);

        $this->logger->log(
            $this->dispatchedLevel,
            "Queue bus dispatched {$name}.",
            ResultContext::from($result)->context(),
        );

        return $result;
    }
}
