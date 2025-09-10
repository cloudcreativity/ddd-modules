<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Queue\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Infrastructure\Queue\QueueMiddleware;
use CloudCreativity\Modules\Contracts\Toolkit\Loggable\ContextFactory;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;
use CloudCreativity\Modules\Toolkit\Loggable\SimpleContextFactory;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final readonly class LogPushedToQueue implements QueueMiddleware
{
    public function __construct(
        private LoggerInterface $log,
        private string $queueLevel = LogLevel::DEBUG,
        private string $queuedLevel = LogLevel::INFO,
        private ContextFactory $context = new SimpleContextFactory(),
    ) {
    }

    public function __invoke(Command $command, Closure $next): void
    {
        $name = ModuleBasename::tryFrom($command)?->toString() ?? $command::class;

        $this->log->log(
            $this->queueLevel,
            "Queuing command {$name}.",
            $context = ['command' => $this->context->make($command)],
        );

        $next($command);

        $this->log->log($this->queuedLevel, "Queued command {$name}.", $context);
    }
}
