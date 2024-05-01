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

use Closure;
use CloudCreativity\Modules\Application\Bus\Middleware\SetupBeforeDispatch;
use CloudCreativity\Modules\Contracts\Application\Messages\Command;
use CloudCreativity\Modules\Contracts\Application\Messages\Query;
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
        $message = $this->createMock(Command::class);
        $result = Result::ok();

        $middleware = new SetupBeforeDispatch(function () {
            $this->sequence[] = 'setup';
            return null;
        });

        $actual = $middleware($message, function ($in) use ($message, $result): Result {
            $this->sequence[] = 'next';
            $this->assertSame($message, $in);
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
        $message = $this->createMock(Query::class);
        $result = Result::ok();

        $middleware = new SetupBeforeDispatch(function (): Closure {
            $this->sequence[] = 'setup';
            return function (): void {
                $this->sequence[] = 'teardown';
            };
        });

        $actual = $middleware($message, function ($in) use ($message, $result): Result {
            $this->sequence[] = 'next';
            $this->assertSame($message, $in);
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
        $message = $this->createMock(Command::class);
        $result = Result::failed('Something went wrong.');

        $middleware = new SetupBeforeDispatch(function () {
            $this->sequence[] = 'setup';
            return null;
        });

        $actual = $middleware($message, function ($in) use ($message, $result): Result {
            $this->sequence[] = 'next';
            $this->assertSame($message, $in);
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
        $message = $this->createMock(Command::class);
        $result = Result::failed('Something went wrong.');

        $middleware = new SetupBeforeDispatch(function (): Closure {
            $this->sequence[] = 'setup';
            return function (): void {
                $this->sequence[] = 'teardown';
            };
        });

        $actual = $middleware($message, function ($in) use ($message, $result): Result {
            $this->sequence[] = 'next';
            $this->assertSame($message, $in);
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
        $message = $this->createMock(Command::class);
        $exception = new RuntimeException('Something went wrong.');
        $actual = null;

        $middleware = new SetupBeforeDispatch(function () {
            $this->sequence[] = 'setup';
            return null;
        });

        try {
            $middleware($message, function ($in) use ($message, $exception): Result {
                $this->sequence[] = 'next';
                $this->assertSame($message, $in);
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
        $message = $this->createMock(Query::class);
        $exception = new RuntimeException('Something went wrong.');
        $actual = null;

        $middleware = new SetupBeforeDispatch(function (): Closure {
            $this->sequence[] = 'setup';
            return function (): void {
                $this->sequence[] = 'teardown';
            };
        });

        try {
            $middleware($message, function ($in) use ($message, $exception): Result {
                $this->sequence[] = 'next';
                $this->assertSame($message, $in);
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
