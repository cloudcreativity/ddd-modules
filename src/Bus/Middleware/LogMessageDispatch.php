<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Bus\MessageInterface;
use CloudCreativity\Modules\Infrastructure\Log\ObjectContext;
use CloudCreativity\Modules\Infrastructure\Log\ResultContext;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class LogMessageDispatch implements MessageMiddlewareInterface
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
    public function __invoke(MessageInterface $message, Closure $next): ResultInterface
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
