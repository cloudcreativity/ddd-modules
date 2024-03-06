<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

namespace CloudCreativity\Modules\Tests\Unit\Bus\Middleware;

use CloudCreativity\Modules\Bus\Middleware\ExecuteInUnitOfWork;
use CloudCreativity\Modules\Infrastructure\Persistence\UnitOfWorkManagerInterface;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
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
        $command = $this->createMock(CommandInterface::class);
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

        $actual = $middleware($command, function ($cmd) use ($command, $expected) {
            $this->assertSame($command, $cmd);
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
        $command = $this->createMock(CommandInterface::class);
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

        $actual = $middleware($command, function ($cmd) use ($command, $expected) {
            $this->assertSame($command, $cmd);
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
        $command = $this->createMock(CommandInterface::class);
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
            $middleware($command, function ($cmd) use ($command, $expected): never {
                $this->assertSame($command, $cmd);
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
