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

use Closure;
use CloudCreativity\Modules\Bus\Middleware\SetupBeforeDispatch;
use CloudCreativity\Modules\Toolkit\Messages\MessageInterface;
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
        $message = $this->createMock(MessageInterface::class);
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
        $message = $this->createMock(MessageInterface::class);
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
        $message = $this->createMock(MessageInterface::class);
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
        $message = $this->createMock(MessageInterface::class);
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
        $message = $this->createMock(MessageInterface::class);
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
        $message = $this->createMock(MessageInterface::class);
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
