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

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\Persistence;

use Closure;
use CloudCreativity\Modules\Infrastructure\InfrastructureException;
use CloudCreativity\Modules\Infrastructure\Log\ExceptionReporterInterface;
use CloudCreativity\Modules\Infrastructure\Persistence\UnitOfWorkInterface;
use CloudCreativity\Modules\Infrastructure\Persistence\UnitOfWorkManager;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UnitOfWorkManagerTest extends TestCase
{
    /**
     * @var UnitOfWorkInterface&MockObject
     */
    private UnitOfWorkInterface&MockObject $unitOfWork;

    /**
     * @var MockObject&ExceptionReporterInterface
     */
    private ExceptionReporterInterface&MockObject $reporter;

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
            $this->reporter = $this->createMock(ExceptionReporterInterface::class),
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
            ->willReturnCallback(function (Closure $callback, int $attempts) use (&$sequence) {
                $this->assertSame(1, $attempts);
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
            ->willReturnCallback(function (Closure $callback, int $attempts) use (&$sequence, $expected) {
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
            ->willReturnCallback(function (Closure $callback, int $attempts) use (&$sequence) {
                $this->assertSame(1, $attempts);
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
            ->willReturnCallback(fn (Closure $callback) => $callback());

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
        $ex = new LogicException('Boom');
        $count = 0;

        $this->reporter
            ->expects($this->exactly(2)) // only "swallowed" exceptions are reported
            ->method('report')
            ->with($this->identicalTo($ex));

        $this->unitOfWork
            ->expects($this->exactly(4))
            ->method('execute')
            ->willReturnCallback(function (\Closure $callback) use ($ex, &$count) {
                $count++;
                $result = $callback();
                if ($count < 4) {
                    throw $ex;
                }
                return $result;
            });

        try {
            $this->manager->execute(function () {
                $this->manager->beforeCommit(fn () => null);
                $this->manager->afterCommit(fn () => null);
            }, 2);
            $this->fail('No exception thrown.');
        } catch (LogicException $ex) {
            $this->assertSame($ex, $ex);
        }

        // this would error if the manager is not empty at this point (tests below prove that).
        $result = $this->manager->execute(fn () => true, 2);

        $this->assertTrue($result);
    }

    /**
     * Scenario in which the callback (not the unit of work) errors, with re-attempts.
     *
     * In this scenario, no before or after commit callbacks should be executed. This is
     * because the callback is executed (and throws) before the inner unit of work is
     * committed. Therefore, no before or after callbacks can be executed.
     *
     * However, in this scenario any callbacks that were registered before the exception
     * is thrown should be forgotten. Otherwise, if the callback is retried, the before
     * callbacks will be executed twice.
     *
     * @return void
     */
    public function testItHandlesCallbackExecutingMultipleTimes(): void
    {
        $ex = new LogicException('Boom!');
        $sequence = [];
        $count = 0;

        $this->reporter
            ->expects($this->exactly(2))
            ->method('report')
            ->with($this->identicalTo($ex));

        $before = [
            function () use (&$sequence): void {
                $sequence[] = 'before1';
            },
            function () use (&$sequence): void {
                $sequence[] = 'before2';
            },
            function () use (&$sequence): void {
                $sequence[] = 'before3';
            },
        ];

        $after = [
            function () use (&$sequence): void {
                $sequence[] = 'after1';
            },
            function () use (&$sequence): void {
                $sequence[] = 'after2';
            },
        ];

        $this->unitOfWork
            ->expects($this->exactly(3))
            ->method('execute')
            ->willReturnCallback(function (Closure $closure) use (&$sequence): mixed {
                try {
                    $sequence[] = 'begin';
                    $result = $closure();
                    $sequence[] = 'committed';
                    return $result;
                } catch (\Throwable $ex) {
                    $sequence[] = 'rollback';
                    throw $ex;
                }
            });

        $this->manager->execute(function () use ($before, $after, $ex, &$count): void {
            $count++;
            $this->manager->afterCommit($after[0]);
            $this->manager->beforeCommit($before[0]);
            $this->manager->beforeCommit($before[1]);

            if ($count < 3) {
                throw $ex;
            }

            $this->manager->beforeCommit($before[2]);
            $this->manager->afterCommit($after[1]);
        }, 3);

        $this->assertSame([
            'begin',
            'rollback',
            'begin',
            'rollback',
            'begin',
            'before1',
            'before2',
            'before3',
            'committed',
            'after1',
            'after2',
        ], $sequence);
    }

    /**
     * Scenario where the inner unit of work fails several times.
     *
     * In this scenario, any before callbacks will be executed multiple times. This
     * is because the manager executes them before the inner unit of work commits
     * (and throws). Therefore, the before callbacks are executed again when the
     * callback is retried and is successful.
     *
     * However, after commit callbacks should not be executed until the successful
     * commit of the inner transaction. They should not be executed more than once,
     * because any that are registered on one of the failed attempts should havbe been
     * forgotten.
     *
     * @return void
     */
    public function testItHandlesUnitOfWorkFailingMultipleTimes(): void
    {
        $ex = new LogicException('Boom!');
        $sequence = [];
        $count = 0;

        $this->reporter
            ->expects($this->exactly(2))
            ->method('report')
            ->with($this->identicalTo($ex));

        $before = [
            function () use (&$sequence): void {
                $sequence[] = 'before1';
            },
            function () use (&$sequence): void {
                $sequence[] = 'before2';
            },
        ];

        $after = [
            function () use (&$sequence): void {
                $sequence[] = 'after1';
            },
            function () use (&$sequence): void {
                $sequence[] = 'after2';
            },
        ];

        $this->unitOfWork
            ->method('execute')
            ->willReturnCallback(function (Closure $closure) use ($ex, &$sequence, &$count): mixed {
                $count++;

                $sequence[] = 'begin';
                $result = $closure();

                if ($count < 3) {
                    $sequence[] = 'rollback';
                    throw $ex;
                }

                $sequence[] = 'committed';
                return $result;
            });

        $this->manager->execute(function () use ($before, $after): void {
            $this->manager->afterCommit($after[0]);
            $this->manager->beforeCommit($before[0]);
            $this->manager->beforeCommit($before[1]);
            $this->manager->afterCommit($after[1]);
        }, 3);

        $this->assertSame([
            'begin',
            'before1',
            'before2',
            'rollback',
            'begin',
            'before1',
            'before2',
            'rollback',
            'begin',
            'before1',
            'before2',
            'committed',
            'after1',
            'after2',
        ], $sequence);
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
            ->willReturnCallback(fn (Closure $callback) => $callback());

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
            ->willReturnCallback(fn (Closure $callback) => $callback());

        $this->expectException(InfrastructureException::class);
        $this->expectExceptionMessage('Cannot queue a before commit callback as unit of work has been committed.');

        $this->manager->execute(function () {
            $this->manager->afterCommit(function () {
                $this->manager->beforeCommit(fn () => null);
            });
        });
    }

    /**
     * @return void
     */
    public function testAttemptsMustBeGreaterThanZero(): void
    {
        $this->expectException(InfrastructureException::class);
        $this->expectExceptionMessage('Attempts must be greater than zero.');

        /**
         * Simulates an infinite loop if the zero attempts is not rejected.
         */
        $this->unitOfWork
            ->expects($this->never()) // ensure we do not attempt it once
            ->method('execute')
            ->willReturnCallback(fn (Closure $callback) => $callback());

        $this->manager->execute(fn () => throw new \LogicException('Boom!'), 0);
    }
}
