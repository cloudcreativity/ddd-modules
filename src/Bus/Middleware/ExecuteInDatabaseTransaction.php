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

namespace CloudCreativity\BalancedEvent\Common\Bus\Middleware;

use Closure;
use CloudCreativity\BalancedEvent\Common\Bus\CommandInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ResultInterface;
use CloudCreativity\BalancedEvent\Common\Infrastructure\Persistence\UnitOfWorkManagerInterface;

class ExecuteInDatabaseTransaction implements CommandMiddlewareInterface
{
    /**
     * ExecuteInDatabaseTransaction constructor.
     *
     * @param UnitOfWorkManagerInterface $transactions
     * @param int $attempts
     */
    public function __construct(
        private readonly UnitOfWorkManagerInterface $transactions,
        private readonly int $attempts = 1
    ) {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(CommandInterface $command, Closure $next): ResultInterface
    {
        return $this->transactions->execute(
            static fn() => $next($command),
            $this->attempts,
        );
    }
}
