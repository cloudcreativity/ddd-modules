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
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class LogPushedToQueue implements QueueMiddlewareInterface
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
    public function __invoke(CommandInterface $command, Closure $next): void
    {
        $name = ModuleBasename::tryFrom($command)?->toString() ?? $command::class;

        $this->log->log(
            $this->queueLevel,
            "Queuing command {$name}.",
            $context = ObjectContext::from($command)->context(),
        );

        $next($command);

        $this->log->log($this->queuedLevel, "Queued command {$name}.", $context);
    }
}
