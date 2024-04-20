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

use CloudCreativity\Modules\Infrastructure\Queue\Middleware\TearDownAfterDispatch;
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;
use CloudCreativity\Modules\Toolkit\Result\Result;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TearDownAfterDispatchTest extends TestCase
{
    /**
     * @return void
     */
    public function testItInvokesCallbackAfterSuccess(): void
    {
        $job = $this->createMock(QueueJobInterface::class);
        $result = Result::ok();
        $sequence = [];

        $middleware = new TearDownAfterDispatch(function () use (&$sequence): void {
            $sequence[] = 'teardown';
        });

        $actual = $middleware($job, function ($in) use ($job, $result, &$sequence): Result {
            $sequence[] = 'next';
            $this->assertSame($job, $in);
            return $result;
        });

        $this->assertSame($result, $actual);
        $this->assertSame(['next', 'teardown'], $sequence);
    }

    /**
     * @return void
     */
    public function testItInvokesCallbackAfterFailure(): void
    {
        $job = $this->createMock(QueueJobInterface::class);
        $result = Result::failed('Something went wrong.');
        $sequence = [];

        $middleware = new TearDownAfterDispatch(function () use (&$sequence): void {
            $sequence[] = 'teardown';
        });

        $actual = $middleware($job, function ($in) use ($job, $result, &$sequence): Result {
            $sequence[] = 'next';
            $this->assertSame($job, $in);
            return $result;
        });

        $this->assertSame($result, $actual);
        $this->assertSame(['next', 'teardown'], $sequence);
    }

    /**
     * @return void
     */
    public function testItInvokesCallbackAfterException(): void
    {
        $job = $this->createMock(QueueJobInterface::class);
        $exception = new RuntimeException('Something went wrong.');
        $actual = null;
        $sequence = [];

        $middleware = new TearDownAfterDispatch(function () use (&$sequence): void {
            $sequence[] = 'teardown';
        });

        try {
            $middleware($job, function ($in) use ($job, $exception, &$sequence): Result {
                $sequence[] = 'next';
                $this->assertSame($job, $in);
                throw $exception;
            });
            $this->fail('No exception thrown.');
        } catch (RuntimeException $ex) {
            $actual = $ex;
        }

        $this->assertSame($exception, $actual);
        $this->assertSame(['next', 'teardown'], $sequence);
    }
}
