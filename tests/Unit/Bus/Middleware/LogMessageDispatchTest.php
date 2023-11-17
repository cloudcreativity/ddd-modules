<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Bus\Middleware;

use CloudCreativity\BalancedEvent\Common\Bus\MessageInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Middleware\LogMessageDispatch;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ResultInterface;
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
        $this->message = $this->createMock(MessageInterface::class);
        $this->message->method('context')->willReturn(['foo' => 'bar']);
    }

    /**
     * @return void
     */
    public function testWithDefaultLevels(): void
    {
        $expected = $this->createMock(ResultInterface::class);
        $expected->method('context')->willReturn(['baz' => 'bat']);
        $name = $this->message::class;

        $this->logger->expects($this->exactly(2))->method('log')->withConsecutive(
            [LogLevel::DEBUG, "Bus dispatching {$name}.", $this->message->context()],
            [LogLevel::INFO, "Bus dispatched {$name}.", $expected->context()],
        );

        $middleware = new LogMessageDispatch($this->logger);
        $actual = $middleware($this->message, function (MessageInterface $received) use ($expected) {
            $this->assertSame($this->message, $received);
            return $expected;
        });

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testWithCustomLevels(): void
    {
        $expected = $this->createMock(ResultInterface::class);
        $expected->method('context')->willReturn(['baz' => 'bat']);
        $name = $this->message::class;

        $this->logger->expects($this->exactly(2))->method('log')->withConsecutive(
            [LogLevel::NOTICE, "Bus dispatching {$name}.", $this->message->context()],
            [LogLevel::WARNING, "Bus dispatched {$name}.", $expected->context()],
        );

        $middleware = new LogMessageDispatch($this->logger, LogLevel::NOTICE, LogLevel::WARNING);
        $actual = $middleware($this->message, function (MessageInterface $received) use ($expected) {
            $this->assertSame($this->message, $received);
            return $expected;
        });

        $this->assertSame($expected, $actual);
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
            ->with(LogLevel::DEBUG, "Bus dispatching {$name}.", $this->message->context());

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
