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
        $this->message = new class () implements QueueableInterface {
            public string $foo = 'baz';
            public string $bar = 'bat';
        };
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $messageName = get_class($this->message);

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $middleware = new LogPushedToQueue($this->logger);
        $middleware($this->message, function (QueueableInterface $received) {
            $this->assertSame($this->message, $received);
        });

        $context = ['foo' => 'baz', 'bar' => 'bat'];

        $this->assertSame([
            [LogLevel::DEBUG, "Queuing job {$messageName}.", $context],
            [LogLevel::INFO, "Queued job {$messageName}.", $context],
        ], $logs);
    }

    /**
     * @return void
     */
    public function testWithCustomLevels(): void
    {
        $messageName = get_class($this->message);
        $logs = [];

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $middleware = new LogPushedToQueue($this->logger, LogLevel::NOTICE, LogLevel::WARNING);
        $middleware($this->message, function (QueueableInterface $received) {
            $this->assertSame($this->message, $received);
        });

        $context = ['foo' => 'baz', 'bar' => 'bat'];

        $this->assertSame([
            [LogLevel::NOTICE, "Queuing job {$messageName}.", $context],
            [LogLevel::WARNING, "Queued job {$messageName}.", $context],
        ], $logs);
    }

    /**
     * @return void
     */
    public function testItLogsAfterTheNextClosureIsInvoked(): void
    {
        $expected = new LogicException();
        $messageName = $this->message::class;
        $context = ['foo' => 'baz', 'bar' => 'bat'];

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::DEBUG, "Queuing job {$messageName}.", $context);

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
