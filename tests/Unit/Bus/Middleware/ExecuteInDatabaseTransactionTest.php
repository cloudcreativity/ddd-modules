<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Bus\Middleware;

use CloudCreativity\BalancedEvent\Common\Bus\CommandInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Middleware\ExecuteInDatabaseTransaction;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ResultInterface;
use CloudCreativity\BalancedEvent\Common\Infrastructure\Persistence\UnitOfWorkManagerInterface;
use PHPUnit\Framework\TestCase;

class ExecuteInDatabaseTransactionTest extends TestCase
{
    public function test(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $expected = $this->createMock(ResultInterface::class);

        $middleware = new ExecuteInDatabaseTransaction(
            $transactions = $this->createMock(UnitOfWorkManagerInterface::class),
            2,
        );

        $transactions
            ->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function (\Closure $callback, int $attempts) {
                $this->assertSame(2, $attempts);
                return $callback();
            });

        $actual = $middleware($command, function ($cmd) use ($command, $expected) {
            $this->assertSame($command, $cmd);
            return $expected;
        });

        $this->assertSame($expected, $actual);
    }
}
