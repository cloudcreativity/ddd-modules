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

use CloudCreativity\Modules\Bus\MessageInterface;
use CloudCreativity\Modules\Bus\Middleware\LogMessageDispatch;
use CloudCreativity\Modules\Infrastructure\Log\ObjectContext;
use CloudCreativity\Modules\Infrastructure\Log\ResultContext;
use CloudCreativity\Modules\Toolkit\Result\Result;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogMessageDispatchTest extends TestCase
{
    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    /**
     * @var MessageInterface
     */
    private MessageInterface $message;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->message = new class () implements MessageInterface {
            public string $foo = 'bar';
            public string $baz = 'bat';
        };
    }

    /**
     * @return void
     */
    public function testWithDefaultLevels(): void
    {
        $expected = Result::ok()->withMeta(['foobar' => 'bazbat']);
        $name = $this->message::class;
        $logs = [];

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $middleware = new LogMessageDispatch($this->logger);
        $actual = $middleware($this->message, function (MessageInterface $received) use ($expected) {
            $this->assertSame($this->message, $received);
            return $expected;
        });

        $this->assertSame($expected, $actual);
        $this->assertSame([
            [LogLevel::DEBUG, "Bus dispatching {$name}.", ObjectContext::from($this->message)->context()],
            [LogLevel::INFO, "Bus dispatched {$name}.", ResultContext::from($expected)->context()],
        ], $logs);
    }

    /**
     * @return void
     */
    public function testWithCustomLevels(): void
    {
        $expected = Result::failed('Something went wrong.');
        $name = $this->message::class;
        $logs = [];

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $middleware = new LogMessageDispatch($this->logger, LogLevel::NOTICE, LogLevel::WARNING);
        $actual = $middleware($this->message, function (MessageInterface $received) use ($expected) {
            $this->assertSame($this->message, $received);
            return $expected;
        });

        $this->assertSame($expected, $actual);
        $this->assertSame([
            [LogLevel::NOTICE, "Bus dispatching {$name}.", ObjectContext::from($this->message)->context()],
            [LogLevel::WARNING, "Bus dispatched {$name}.", ResultContext::from($expected)->context()],
        ], $logs);
    }

    /**
     * @return void
     */
    public function testItLogsAfterTheNextClosureIsInvoked(): void
    {
        $expected = new LogicException();
        $name = $this->message::class;

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::DEBUG, "Bus dispatching {$name}.", ObjectContext::from($this->message)->context());

        $middleware = new LogMessageDispatch($this->logger);

        try {
            $middleware($this->message, static function () use ($expected) {
                throw $expected;
            });
            $this->fail('No exception thrown.');
        } catch (LogicException $ex) {
            $this->assertSame($expected, $ex);
        }
    }
}
