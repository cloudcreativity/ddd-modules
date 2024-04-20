<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\Queue\Middleware;

use CloudCreativity\Modules\Infrastructure\Persistence\UnitOfWorkManagerInterface;
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\ExecuteInUnitOfWork;
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;
use CloudCreativity\Modules\Toolkit\Result\Result;
use PHPUnit\Framework\TestCase;
use Throwable;

class ExecuteInUnitOfWorkTest extends TestCase
{
    /**
     * @var array<string>
     */
    private array $sequence = [];

    /**
     * @return void
     */
    public function testItCommitsUnitOfWorkOnSuccess(): void
    {
        $job = $this->createMock(QueueJobInterface::class);
        $expected = Result::ok();

        $middleware = new ExecuteInUnitOfWork(
            $transactions = $this->createMock(UnitOfWorkManagerInterface::class),
            2,
        );

        $transactions
            ->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function (\Closure $callback, int $attempts) {
                $this->assertSame(2, $attempts);
                $this->sequence[] = 'begin';
                $result = $callback();
                $this->sequence[] = 'commit';
                return $result;
            });

        $actual = $middleware($job, function ($cmd) use ($job, $expected) {
            $this->assertSame($job, $cmd);
            $this->sequence[] = 'handler';
            return $expected;
        });

        $this->assertSame(['begin', 'handler', 'commit'], $this->sequence);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItDoesNotCommitUnitOfWorkOnFailure(): void
    {
        $job = $this->createMock(QueueJobInterface::class);
        $expected = Result::failed('Something went wrong.');

        $middleware = new ExecuteInUnitOfWork(
            $transactions = $this->createMock(UnitOfWorkManagerInterface::class),
            2,
        );

        $transactions
            ->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function (\Closure $callback, int $attempts) {
                $this->assertSame(2, $attempts);
                $this->sequence[] = 'begin';
                $result = $callback();
                $this->sequence[] = 'commit';
                return $result;
            });

        $actual = $middleware($job, function ($cmd) use ($job, $expected) {
            $this->assertSame($job, $cmd);
            $this->sequence[] = 'handler';
            return $expected;
        });

        $this->assertSame(['begin', 'handler'], $this->sequence);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItDoesNotCatchExceptions(): void
    {
        $job = $this->createMock(QueueJobInterface::class);
        $expected = new \RuntimeException('Boom! Something went wrong.');

        $middleware = new ExecuteInUnitOfWork(
            $transactions = $this->createMock(UnitOfWorkManagerInterface::class),
            2,
        );

        $transactions
            ->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function (\Closure $callback, int $attempts) {
                $this->assertSame(2, $attempts);
                $this->sequence[] = 'begin';
                $result = $callback();
                $this->sequence[] = 'commit';
                return $result;
            });

        $actual = null;

        try {
            $middleware($job, function ($cmd) use ($job, $expected): never {
                $this->assertSame($job, $cmd);
                $this->sequence[] = 'handler';
                throw $expected;
            });
        } catch (Throwable $ex) {
            $actual = $ex;
        }

        $this->assertSame(['begin', 'handler'], $this->sequence);
        $this->assertSame($expected, $actual);
    }
}
