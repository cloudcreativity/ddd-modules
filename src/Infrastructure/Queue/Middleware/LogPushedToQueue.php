<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Queue\Middleware;

use Closure;
use CloudCreativity\Modules\Bus\SanitizedMessage;
use CloudCreativity\Modules\Contracts\Bus\Middleware\CommandMiddleware;
use CloudCreativity\Modules\Contracts\Messaging\Command;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final readonly class LogPushedToQueue implements CommandMiddleware
{
    public function __construct(
        private LoggerInterface $log,
        private string $queueLevel = LogLevel::DEBUG,
        private string $queuedLevel = LogLevel::INFO,
    ) {
    }

    public function __invoke(Command $command, Closure $next): null
    {
        $name = ModuleBasename::tryFrom($command)?->toString() ?? $command::class;

        $this->log->log(
            $this->queueLevel,
            "Queuing command {$name}.",
            $context = ['command' => new SanitizedMessage($command)->context()],
        );

        $next($command);

        $this->log->log($this->queuedLevel, "Queued command {$name}.", $context);

        return null;
    }
}
