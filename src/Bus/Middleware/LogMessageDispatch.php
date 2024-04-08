<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Toolkit\Loggable\ObjectContext;
use CloudCreativity\Modules\Toolkit\Loggable\ResultContext;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Messages\QueryInterface;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;
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
    public function __invoke(CommandInterface|QueryInterface $message, Closure $next): ResultInterface
    {
        $name = ModuleBasename::tryFrom($message)?->toString() ?? $message::class;

        $this->logger->log(
            $this->dispatchLevel,
            "Bus dispatching {$name}.",
            ObjectContext::from($message)->context(),
        );

        /** @var ResultInterface<mixed> $result */
        $result = $next($message);

        $this->logger->log(
            $this->dispatchedLevel,
            "Bus dispatched {$name}.",
            ResultContext::from($result)->context(),
        );

        return $result;
    }
}
