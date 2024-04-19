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

use Closure;
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\SetupBeforeDispatch;
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;
use CloudCreativity\Modules\Toolkit\Result\Result;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SetupBeforeDispatchTest extends TestCase
{
    /**
     * @var array<string>
     */
    private array $sequence = [];

    /**
     * @return void
     */
    public function testItSetsUpAndSucceedsWithoutTeardown(): void
    {
        $job = $this->createMock(QueueJobInterface::class);
        $result = Result::ok();

        $middleware = new SetupBeforeDispatch(function () {
            $this->sequence[] = 'setup';
            return null;
        });

        $actual = $middleware($job, function ($in) use ($job, $result): Result {
            $this->sequence[] = 'next';
            $this->assertSame($job, $in);
            return $result;
        });

        $this->assertSame($result, $actual);
        $this->assertSame(['setup', 'next'], $this->sequence);
    }

    /**
     * @return void
     */
    public function testItSetsUpAndSucceedsWithTeardown(): void
    {
        $job = $this->createMock(QueueJobInterface::class);
        $result = Result::ok();

        $middleware = new SetupBeforeDispatch(function (): Closure {
            $this->sequence[] = 'setup';
            return function (): void {
                $this->sequence[] = 'teardown';
            };
        });

        $actual = $middleware($job, function ($in) use ($job, $result): Result {
            $this->sequence[] = 'next';
            $this->assertSame($job, $in);
            return $result;
        });

        $this->assertSame($result, $actual);
        $this->assertSame(['setup', 'next', 'teardown'], $this->sequence);
    }

    /**
     * @return void
     */
    public function testItSetsUpAndFailsWithoutTeardown(): void
    {
        $job = $this->createMock(QueueJobInterface::class);
        $result = Result::failed('Something went wrong.');

        $middleware = new SetupBeforeDispatch(function () {
            $this->sequence[] = 'setup';
            return null;
        });

        $actual = $middleware($job, function ($in) use ($job, $result): Result {
            $this->sequence[] = 'next';
            $this->assertSame($job, $in);
            return $result;
        });

        $this->assertSame($result, $actual);
        $this->assertSame(['setup', 'next'], $this->sequence);
    }

    /**
     * @return void
     */
    public function testItSetsUpAndFailsWithTeardown(): void
    {
        $job = $this->createMock(QueueJobInterface::class);
        $result = Result::failed('Something went wrong.');

        $middleware = new SetupBeforeDispatch(function (): Closure {
            $this->sequence[] = 'setup';
            return function (): void {
                $this->sequence[] = 'teardown';
            };
        });

        $actual = $middleware($job, function ($in) use ($job, $result): Result {
            $this->sequence[] = 'next';
            $this->assertSame($job, $in);
            return $result;
        });

        $this->assertSame($result, $actual);
        $this->assertSame(['setup', 'next', 'teardown'], $this->sequence);
    }

    /**
     * @return void
     */
    public function testItSetsUpAndThrowsExceptionWithoutTeardown(): void
    {
        $job = $this->createMock(QueueJobInterface::class);
        $exception = new RuntimeException('Something went wrong.');
        $actual = null;

        $middleware = new SetupBeforeDispatch(function () {
            $this->sequence[] = 'setup';
            return null;
        });

        try {
            $middleware($job, function ($in) use ($job, $exception): Result {
                $this->sequence[] = 'next';
                $this->assertSame($job, $in);
                throw $exception;
            });
            $this->fail('No exception thrown.');
        } catch (RuntimeException $ex) {
            $actual = $ex;
        }

        $this->assertSame($exception, $actual);
        $this->assertSame(['setup', 'next'], $this->sequence);
    }

    /**
     * @return void
     */
    public function testItSetsUpAndThrowsExceptionWithTeardown(): void
    {
        $job = $this->createMock(QueueJobInterface::class);
        $exception = new RuntimeException('Something went wrong.');
        $actual = null;

        $middleware = new SetupBeforeDispatch(function (): Closure {
            $this->sequence[] = 'setup';
            return function (): void {
                $this->sequence[] = 'teardown';
            };
        });

        try {
            $middleware($job, function ($in) use ($job, $exception): Result {
                $this->sequence[] = 'next';
                $this->assertSame($job, $in);
                throw $exception;
            });
            $this->fail('No exception thrown.');
        } catch (RuntimeException $ex) {
            $actual = $ex;
        }

        $this->assertSame($exception, $actual);
        $this->assertSame(['setup', 'next', 'teardown'], $this->sequence);
    }
}
