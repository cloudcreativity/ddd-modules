<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Application\Bus\BusMiddleware;
use CloudCreativity\Modules\Contracts\Toolkit\Loggable\ContextFactory;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;
use CloudCreativity\Modules\Toolkit\Loggable\SimpleContextFactory;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class LogMessageDispatch implements BusMiddleware
{
    /**
     * LogMessageDispatch constructor.
     *
     * @param LoggerInterface $logger
     * @param string $dispatchLevel
     * @param string $dispatchedLevel
     * @param ContextFactory $context
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $dispatchLevel = LogLevel::DEBUG,
        private readonly string $dispatchedLevel = LogLevel::INFO,
        private readonly ContextFactory $context = new SimpleContextFactory(),
    ) {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(Command|Query $message, Closure $next): Result
    {
        $name = ModuleBasename::tryFrom($message)?->toString() ?? $message::class;
        $key = ($message instanceof Command) ? 'command' : 'query';

        $this->logger->log(
            $this->dispatchLevel,
            "Bus dispatching {$name}.",
            [$key => $this->context->make($message)],
        );

        /** @var Result<mixed> $result */
        $result = $next($message);

        $this->logger->log(
            $this->dispatchedLevel,
            "Bus dispatched {$name}.",
            ['result' => $this->context->make($result)],
        );

        return $result;
    }
}
