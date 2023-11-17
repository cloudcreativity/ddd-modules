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

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\Queue\Middleware;

use CloudCreativity\Modules\Infrastructure\Queue\Middleware\LogPushedToQueue;
use CloudCreativity\Modules\Infrastructure\Queue\QueueableInterface;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogPushedToQueueTest extends TestCase
{
    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    /**
     * @var QueueableInterface
     */
    private QueueableInterface $message;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->message = $this->createMock(QueueableInterface::class);
        $this->message->method('context')->willReturn(['foo' => 'bar']);
    }

    public function test(): void
    {
        $messageName = get_class($this->message);

        $this->logger->expects($this->exactly(2))->method('log')->withConsecutive(
            [LogLevel::DEBUG, "Queuing message {$messageName}.", $this->message->context()],
            [LogLevel::INFO, "Queued message {$messageName}.", $this->message->context()],
        );

        $middleware = new LogPushedToQueue($this->logger);
        $middleware($this->message, function (QueueableInterface $received) {
            $this->assertSame($this->message, $received);
        });
    }

    public function testWithCustomLevels(): void
    {
        $messageName = get_class($this->message);

        $this->logger->expects($this->exactly(2))->method('log')->withConsecutive(
            [LogLevel::NOTICE, "Queuing message {$messageName}.", $this->message->context()],
            [LogLevel::WARNING, "Queued message {$messageName}.", $this->message->context()],
        );

        $middleware = new LogPushedToQueue($this->logger, LogLevel::NOTICE, LogLevel::WARNING);
        $middleware($this->message, function (QueueableInterface $received) {
            $this->assertSame($this->message, $received);
        });
    }

    public function testItLogsAfterTheNextClosureIsInvoked(): void
    {
        $expected = new LogicException();
        $messageName = get_class($this->message);

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::DEBUG, "Queuing message {$messageName}.", $this->message->context());

        $middleware = new LogPushedToQueue($this->logger);

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
