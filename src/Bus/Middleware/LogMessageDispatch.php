<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Bus\SanitizedMessage;
use CloudCreativity\Modules\Contracts\Bus\Middleware\BusMiddleware;
use CloudCreativity\Modules\Contracts\Messaging\Command;
use CloudCreativity\Modules\Contracts\Messaging\IntegrationEvent;
use CloudCreativity\Modules\Contracts\Messaging\Message;
use CloudCreativity\Modules\Contracts\Messaging\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final readonly class LogMessageDispatch implements BusMiddleware
{
    public function __construct(
        private LoggerInterface $logger,
        private string $dispatchLevel = LogLevel::DEBUG,
        private string $dispatchedLevel = LogLevel::INFO,
    ) {
    }

    public function __invoke(Message $message, Closure $next): ?Result
    {
        $name = ModuleBasename::tryFrom($message)?->toString() ?? $message::class;
        $key = match (true) {
            $message instanceof Command => 'command',
            $message instanceof Query => 'query',
            $message instanceof IntegrationEvent => 'event',
            default => 'message',
        };

        $this->logger->log(
            $this->dispatchLevel,
            "Bus dispatching {$name}.",
            [$key => new SanitizedMessage($message)->context()],
        );

        $result = $next($message);

        $this->logger->log(
            $this->dispatchedLevel,
            "Bus dispatched {$name}.",
            $result ? ['result' => $result->context()] : [],
        );

        return $result;
    }
}
