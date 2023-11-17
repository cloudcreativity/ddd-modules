<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
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
