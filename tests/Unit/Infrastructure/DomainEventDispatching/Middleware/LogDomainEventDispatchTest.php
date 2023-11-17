<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Infrastructure\DomainEventDispatching\Middleware;

use CloudCreativity\BalancedEvent\Common\Domain\Events\DomainEventInterface;
use CloudCreativity\BalancedEvent\Common\Infrastructure\DomainEventDispatching\Middleware\LogDomainEventDispatch;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogDomainEventDispatchTest extends TestCase
{
    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    /**
     * @var DomainEventInterface
     */
    private DomainEventInterface $event;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->event = $this->createMock(DomainEventInterface::class);
    }

    /**
     * @return void
     */
    public function testWithDefaultLevels(): void
    {
        $eventName = $this->event::class;

        $this->logger->expects($this->exactly(2))->method('log')->withConsecutive(
            [LogLevel::DEBUG, "Dispatching event {$eventName}."],
            [LogLevel::INFO, "Dispatched event {$eventName}."],
        );

        $middleware = new LogDomainEventDispatch($this->logger);
        $middleware($this->event, function (DomainEventInterface $received) {
            $this->assertSame($this->event, $received);
        });
    }

    /**
     * @return void
     */
    public function testWithCustomLevels(): void
    {
        $eventName = $this->event::class;

        $this->logger->expects($this->exactly(2))->method('log')->withConsecutive(
            [LogLevel::NOTICE, "Dispatching event {$eventName}."],
            [LogLevel::WARNING, "Dispatched event {$eventName}."],
        );

        $middleware = new LogDomainEventDispatch($this->logger, LogLevel::NOTICE, LogLevel::WARNING);
        $middleware($this->event, function (DomainEventInterface $received) {
            $this->assertSame($this->event, $received);
        });
    }

    /**
     * @return void
     */
    public function testItLogsAfterTheNextClosureIsInvoked(): void
    {
        $expected = new LogicException();
        $eventName = $this->event::class;

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::DEBUG, "Dispatching event {$eventName}.");

        $middleware = new LogDomainEventDispatch($this->logger);

        try {
            $middleware($this->event, static function () use ($expected) {
                throw $expected;
            });
            $this->fail('No exception thrown.');
        } catch (LogicException $ex) {
            $this->assertSame($expected, $ex);
        }
    }
}
