<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Infrastructure\Queue\Middleware;

use CloudCreativity\BalancedEvent\Common\Infrastructure\Queue\Middleware\LogPushedToQueue;
use CloudCreativity\BalancedEvent\Common\Infrastructure\Queue\QueueableInterface;
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
