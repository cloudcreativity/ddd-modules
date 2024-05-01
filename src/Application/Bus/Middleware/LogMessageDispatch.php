<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Application\Messages\Command;
use CloudCreativity\Modules\Contracts\Application\Messages\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;
use CloudCreativity\Modules\Toolkit\Loggable\ObjectContext;
use CloudCreativity\Modules\Toolkit\Loggable\ResultContext;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class LogMessageDispatch implements BusMiddlewareInterface
{
    /**
     * LogMessageDispatch constructor.
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
    public function __invoke(Command|Query $message, Closure $next): Result
    {
        $name = ModuleBasename::tryFrom($message)?->toString() ?? $message::class;

        $this->logger->log(
            $this->dispatchLevel,
            "Bus dispatching {$name}.",
            ObjectContext::from($message)->context(),
        );

        /** @var Result<mixed> $result */
        $result = $next($message);

        $this->logger->log(
            $this->dispatchedLevel,
            "Bus dispatched {$name}.",
            ResultContext::from($result)->context(),
        );

        return $result;
    }
}
