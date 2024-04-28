<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\Bus\Middleware;

use CloudCreativity\Modules\Application\Bus\Middleware\TearDownAfterDispatch;
use CloudCreativity\Modules\Application\Messages\CommandInterface;
use CloudCreativity\Modules\Application\Messages\QueryInterface;
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
        $message = $this->createMock(CommandInterface::class);
        $result = Result::ok();
        $sequence = [];

        $middleware = new TearDownAfterDispatch(function () use (&$sequence): void {
            $sequence[] = 'teardown';
        });

        $actual = $middleware($message, function ($in) use ($message, $result, &$sequence): Result {
            $sequence[] = 'next';
            $this->assertSame($message, $in);
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
        $message = $this->createMock(QueryInterface::class);
        $result = Result::failed('Something went wrong.');
        $sequence = [];

        $middleware = new TearDownAfterDispatch(function () use (&$sequence): void {
            $sequence[] = 'teardown';
        });

        $actual = $middleware($message, function ($in) use ($message, $result, &$sequence): Result {
            $sequence[] = 'next';
            $this->assertSame($message, $in);
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
        $message = $this->createMock(CommandInterface::class);
        $exception = new RuntimeException('Something went wrong.');
        $actual = null;
        $sequence = [];

        $middleware = new TearDownAfterDispatch(function () use (&$sequence): void {
            $sequence[] = 'teardown';
        });

        try {
            $middleware($message, function ($in) use ($message, $exception, &$sequence): Result {
                $sequence[] = 'next';
                $this->assertSame($message, $in);
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
