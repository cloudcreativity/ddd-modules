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
use CloudCreativity\Modules\Infrastructure\Log\ObjectContext;
use CloudCreativity\Modules\Infrastructure\Log\ResultContext;
use CloudCreativity\Modules\Infrastructure\Queue\QueueableInterface;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class LogQueueableDispatch implements QueueableMiddlewareInterface
{
    /**
     * LogPushedToQueue constructor.
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
    public function __invoke(QueueableInterface $queueable, Closure $next): ResultInterface
    {
        $name = ModuleBasename::tryFrom($queueable)?->toString() ?? $queueable::class;

        $this->log->log(
            $this->queueLevel,
            "Dispatching queued job {$name}.",
            ObjectContext::from($queueable)->context(),
        );

        $result = $next($queueable);

        $this->log->log(
            $this->queuedLevel,
            "Dispatched queued job {$name}.",
            ResultContext::from($result)->context(),
        );

        return $result;
    }
}
