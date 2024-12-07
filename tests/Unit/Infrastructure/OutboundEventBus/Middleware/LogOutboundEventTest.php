<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\OutboundEventBus\Middleware;

use CloudCreativity\Modules\Contracts\Application\Messages\IntegrationEvent;
use CloudCreativity\Modules\Infrastructure\OutboundEventBus\Middleware\LogOutboundEvent;
use CloudCreativity\Modules\Tests\Unit\Infrastructure\OutboundEventBus\TestOutboundEvent;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogOutboundEventTest extends TestCase
{
    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    /**
     * @var TestOutboundEvent
     */
    private TestOutboundEvent $event;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->event = new TestOutboundEvent();
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $eventName = ModuleBasename::from($this->event);

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $middleware = new LogOutboundEvent($this->logger);
        $middleware($this->event, function (IntegrationEvent $received): void {
            $this->assertSame($this->event, $received);
        });

        $context = [
            'uuid' => $this->event->uuid,
            'occurredAt' => $this->event->occurredAt,
        ];

        $this->assertSame([
            [LogLevel::DEBUG, "Publishing integration event {$eventName}.", $context],
            [LogLevel::INFO, "Published integration event {$eventName}.", $context],
        ], $logs);
    }

    /**
     * @return void
     */
    public function testWithCustomLevels(): void
    {
        $eventName = ModuleBasename::from($this->event);
        $logs = [];

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $middleware = new LogOutboundEvent($this->logger, LogLevel::NOTICE, LogLevel::WARNING);
        $middleware($this->event, function (IntegrationEvent $received) {
            $this->assertSame($this->event, $received);
        });

        $context = [
            'uuid' => $this->event->uuid,
            'occurredAt' => $this->event->occurredAt,
        ];

        $this->assertSame([
            [LogLevel::NOTICE, "Publishing integration event {$eventName}.", $context],
            [LogLevel::WARNING, "Published integration event {$eventName}.", $context],
        ], $logs);
    }

    /**
     * @return void
     */
    public function testItLogsAfterTheNextClosureIsInvoked(): void
    {
        $expected = new LogicException();
        $eventName = ModuleBasename::from($this->event);

        $context = [
            'uuid' => $this->event->uuid,
            'occurredAt' => $this->event->occurredAt,
        ];

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::DEBUG, "Publishing integration event {$eventName}.", $context);

        $middleware = new LogOutboundEvent($this->logger);

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
