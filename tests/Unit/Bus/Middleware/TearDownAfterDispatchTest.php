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

namespace CloudCreativity\Modules\Tests\Unit\Bus\Middleware;

use CloudCreativity\Modules\Bus\MessageInterface;
use CloudCreativity\Modules\Bus\Middleware\TearDownAfterDispatch;
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
        $message = $this->createMock(MessageInterface::class);
        $result = Result::ok();
        $sequence = [];

        $middleware = new TearDownAfterDispatch(function () use (&$sequence): void {
            $sequence[] = 'teardown';
        });

        $actual = $middleware($message, function () use ($result, &$sequence): Result {
            $sequence[] = 'next';
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
        $message = $this->createMock(MessageInterface::class);
        $result = Result::failed('Something went wrong.');
        $sequence = [];

        $middleware = new TearDownAfterDispatch(function () use (&$sequence): void {
            $sequence[] = 'teardown';
        });

        $actual = $middleware($message, function () use ($result, &$sequence): Result {
            $sequence[] = 'next';
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
        $message = $this->createMock(MessageInterface::class);
        $exception = new RuntimeException('Something went wrong.');
        $actual = null;
        $sequence = [];

        $middleware = new TearDownAfterDispatch(function () use (&$sequence): void {
            $sequence[] = 'teardown';
        });

        try {
            $middleware($message, function () use ($exception, &$sequence): Result {
                $sequence[] = 'next';
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
