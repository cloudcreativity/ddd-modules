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

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\Persistence;

use CloudCreativity\Modules\Infrastructure\InfrastructureException;
use CloudCreativity\Modules\Infrastructure\Persistence\UnitOfWorkInterface;
use CloudCreativity\Modules\Infrastructure\Persistence\UnitOfWorkManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UnitOfWorkManagerTest extends TestCase
{
    /**
     * @var UnitOfWorkInterface&MockObject
     */
    private UnitOfWorkInterface&MockObject $unitOfWork;

    /**
     * @var UnitOfWorkManager
     */
    private UnitOfWorkManager $manager;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new UnitOfWorkManager(
            $this->unitOfWork = $this->createMock(UnitOfWorkInterface::class),
        );
    }

    /**
     * @return void
     */
    public function testItExecutesCallbacks(): void
    {
        $sequence = [];

        $this->unitOfWork
            ->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function (\Closure $callback, int $attempts) use (&$sequence) {
                $this->assertSame(2, $attempts);
                $sequence[] = 'start';
                $result = $callback();
                $sequence[] = 'commit';
                return $result;
            });

        $before1 = function () use (&$sequence): void {
            $this->assertSame(['start'], $sequence);
            $sequence[] = 'before1';
        };

        $before2 = function () use (&$sequence): void {
            $this->assertSame(['start', 'before1'], $sequence);
            $sequence[] = 'before2';
        };

        $after1 = function () use (&$sequence): void {
            $this->assertSame(['start', 'before1', 'before2', 'commit'], $sequence);
            $sequence[] = 'after1';
        };

        $after2 = function () use (&$sequence): void {
            $this->assertSame(['start', 'before1', 'before2', 'commit', 'after1'], $sequence);
            $sequence[] = 'after2';
        };

        $expected = new \DateTime();

        $actual = $this->manager->execute(function () use ($before1, $before2, $after1, $after2, $expected) {
            $this->manager->beforeCommit($before1);
            $this->manager->afterCommit($after1);
            $this->manager->beforeCommit($before2);
            $this->manager->afterCommit($after2);
            return $expected;
        }, 2);

        $this->assertSame(['start', 'before1', 'before2', 'commit', 'after1', 'after2'], $sequence);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItExecutesCallbackWhenTransactionFailsToCommit(): void
    {
        $expected = new \RuntimeException('Boom');
        $sequence = [];

        $this->unitOfWork
            ->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function (\Closure $callback, int $attempts) use (&$sequence, $expected) {
                $this->assertSame(1, $attempts);
                $sequence[] = 'start';
                $callback();
                throw $expected;
            });

        $before1 = function () use (&$sequence): void {
            $this->assertSame(['start'], $sequence);
            $sequence[] = 'before1';
        };

        $before2 = function () use (&$sequence): void {
            $this->assertSame(['start', 'before1'], $sequence);
            $sequence[] = 'before2';
        };

        $after = function (): void {
            $this->fail('Not expecting after callback to be executed.');
        };

        try {
            $this->manager->execute(function () use ($before1, $before2, $after) {
                $this->manager->beforeCommit($before1);
                $this->manager->afterCommit($after);
                $this->manager->beforeCommit($before2);
            });
            $this->fail('No exception thrown.');
        } catch (\RuntimeException $ex) {
            $this->assertSame($expected, $ex);
        }

        $this->assertSame(['start', 'before1', 'before2'], $sequence);
    }

    /**
     * If any callbacks get registered by other callbacks, they are executed.
     *
     * @return void
     */
    public function testItExecutesAdditionalCommitCallbacks(): void
    {
        $sequence = [];

        $this->unitOfWork
            ->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function (\Closure $callback, int $attempts) use (&$sequence) {
                $this->assertSame(2, $attempts);
                $sequence[] = 'start';
                $result = $callback();
                $sequence[] = 'commit';
                return $result;
            });

        $before = [
            function () use (&$sequence): void {
                $this->assertSame(['start'], $sequence);
                $sequence[] = 'before1';
            },
            function () use (&$sequence): void {
                $this->assertSame(['start', 'before1'], $sequence);
                $sequence[] = 'before2';
            },
            function () use (&$sequence): void {
                $this->assertSame(['start', 'before1', 'before2'], $sequence);
                $sequence[] = 'before3';
            },
        ];

        $after = [
            function () use (&$sequence): void {
                $this->assertSame(['start', 'before1', 'before2', 'before3', 'commit'], $sequence);
                $sequence[] = 'after1';
            },
            function () use (&$sequence): void {
                $this->assertSame(['start', 'before1', 'before2', 'before3', 'commit', 'after1'], $sequence);
                $sequence[] = 'after2';
            },
            function () use (&$sequence): void {
                $this->assertSame(
                    ['start', 'before1', 'before2', 'before3', 'commit', 'after1', 'after2'],
                    $sequence,
                );
                $sequence[] = 'after3';
            },
        ];

        $expected = new \DateTime();

        $actual = $this->manager->execute(function () use ($before, $after, $expected) {
            $this->manager->beforeCommit($before[0]);
            $this->manager->beforeCommit($before[1]);
            $this->manager->afterCommit($after[0]);
            $this->manager->beforeCommit(function () use ($before, $after) {
                $this->manager->beforeCommit($before[2]);
                $this->manager->afterCommit($after[1]);
            });
            $this->manager->afterCommit(function () use ($after) {
                $this->manager->afterCommit($after[2]);
            });
            return $expected;
        }, 2);

        $this->assertSame([
            'start',
            'before1',
            'before2',
            'before3',
            'commit',
            'after1',
            'after2',
            'after3',
        ], $sequence);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItFlushesCallbacksOnSuccessfulTransaction(): void
    {
        $this->unitOfWork
            ->method('execute')
            ->willReturnCallback(fn (\Closure $callback) => $callback());

        $result1 = $this->manager->execute(function () {
            $this->manager->beforeCommit(fn () => null);
            $this->manager->afterCommit(fn () => null);
            return 1;
        });

        // this would error if the manager is not empty at this point (tests below prove that).
        $result2 = $this->manager->execute(fn () => 2);

        $this->assertSame(1, $result1);
        $this->assertSame(2, $result2);
    }

    /**
     * @return void
     */
    public function testItFlushesCallbacksOnUnsuccessfulTransaction(): void
    {
        $expected = new \RuntimeException('Boom');

        $this->unitOfWork
            ->method('execute')
            ->willReturnCallback(function (\Closure $callback, int $attempts) use ($expected) {
                $result = $callback();
                if (1 === $attempts) {
                    throw $expected;
                }
                return $result;
            });

        try {
            $this->manager->execute(function () {
                $this->manager->beforeCommit(fn () => null);
                $this->manager->afterCommit(fn () => null);
            });
            $this->fail('No exception thrown.');
        } catch (\RuntimeException $ex) {
            $this->assertSame($expected, $ex);
        }

        // this would error if the manager is not empty at this point (tests below prove that).
        $result = $this->manager->execute(fn () => true, 2);

        $this->assertTrue($result);
    }

    /**
     * @return void
     */
    public function testItFailsIfBeforeCallbacksAreQueuedBeforeTransaction(): void
    {
        $this->unitOfWork
            ->expects($this->never())
            ->method('execute');

        $this->expectException(InfrastructureException::class);
        $this->expectExceptionMessage('Cannot queue a before commit callback when not executing a unit of work.');

        $this->manager->beforeCommit(fn () => null);
    }

    /**
     * @return void
     */
    public function testItFailsIfAfterCallbacksAreQueuedBeforeTransaction(): void
    {
        $this->expectException(InfrastructureException::class);
        $this->expectExceptionMessage('Cannot queue an after commit callback when not executing a unit of work.');

        $this->manager->afterCommit(fn () => null);
    }

    /**
     * @return void
     */
    public function testItCannotStartTransactionWithinAnExistingOne(): void
    {
        $this->unitOfWork
            ->expects($this->once())
            ->method('execute')
            ->willReturnCallback(fn (\Closure $callback) => $callback());

        $this->expectException(InfrastructureException::class);
        $this->expectExceptionMessage(
            'Not expecting unit of work manager to start a unit of work within an existing one.',
        );

        $this->manager->execute(function () {
            $this->manager->execute(fn () => null);
        });
    }

    /**
     * @return void
     */
    public function testItFailsIfAfterCommitCallbackRegistersBeforeCommitCallback(): void
    {
        $this->unitOfWork
            ->method('execute')
            ->willReturnCallback(fn (\Closure $callback) => $callback());

        $this->expectException(InfrastructureException::class);
        $this->expectExceptionMessage('Cannot queue a before commit callback as unit of work has been committed.');

        $this->manager->execute(function () {
            $this->manager->afterCommit(function () {
                $this->manager->beforeCommit(fn () => null);
            });
        });
    }
}
