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

namespace CloudCreativity\Modules\Tests\Unit\EventBus\Middleware;

use CloudCreativity\Modules\EventBus\Middleware\LogInboundIntegrationEvent;
use CloudCreativity\Modules\Tests\Unit\EventBus\TestIntegrationEvent;
use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogInboundIntegrationEventTest extends TestCase
{
    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    /**
     * @var TestIntegrationEvent
     */
    private TestIntegrationEvent $event;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->event = new TestIntegrationEvent();
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

        $middleware = new LogInboundIntegrationEvent($this->logger);
        $middleware($this->event, function (IntegrationEventInterface $received): void {
            $this->assertSame($this->event, $received);
        });

        $context = [
            'uuid' => $this->event->uuid,
            'occurredAt' => $this->event->occurredAt,
        ];

        $this->assertSame([
            [LogLevel::DEBUG, "Receiving integration event {$eventName}.", $context],
            [LogLevel::INFO, "Received integration event {$eventName}.", $context],
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

        $middleware = new LogInboundIntegrationEvent($this->logger, LogLevel::NOTICE, LogLevel::WARNING);
        $middleware($this->event, function (IntegrationEventInterface $received) {
            $this->assertSame($this->event, $received);
        });

        $context = [
            'uuid' => $this->event->uuid,
            'occurredAt' => $this->event->occurredAt,
        ];

        $this->assertSame([
            [LogLevel::NOTICE, "Receiving integration event {$eventName}.", $context],
            [LogLevel::WARNING, "Received integration event {$eventName}.", $context],
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
            ->with(LogLevel::DEBUG, "Receiving integration event {$eventName}.", $context);

        $middleware = new LogInboundIntegrationEvent($this->logger);

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
