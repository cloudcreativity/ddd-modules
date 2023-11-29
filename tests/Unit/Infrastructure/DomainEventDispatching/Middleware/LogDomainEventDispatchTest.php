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

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\DomainEventDispatching\Middleware;

use CloudCreativity\Modules\Domain\Events\DomainEventInterface;
use CloudCreativity\Modules\Infrastructure\DomainEventDispatching\Middleware\LogDomainEventDispatch;
use DateTimeImmutable;
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

        /**
         * Do not expect any public properties to be logged as context.
         * This is because the event is in the domain layer, so cannot implement the
         * log context provider interface to override any context logging. That means
         * if we automatically logged context there would be no way for the developer
         * to prevent sensitive properties from being logged.
         */
        $this->event = new class () implements DomainEventInterface {
            public string $foo = 'foo';
            public string $bar = 'bar';

            public function occurredAt(): DateTimeImmutable
            {
                return new DateTimeImmutable();
            }
        };
    }

    /**
     * @return void
     */
    public function testWithDefaultLevels(): void
    {
        $eventName = $this->event::class;
        $logs = [];

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $middleware = new LogDomainEventDispatch($this->logger);
        $middleware($this->event, function (DomainEventInterface $received) {
            $this->assertSame($this->event, $received);
        });

        $this->assertSame([
            [LogLevel::DEBUG, "Dispatching event {$eventName}.", []],
            [LogLevel::INFO, "Dispatched event {$eventName}.", []],
        ], $logs);
    }

    /**
     * @return void
     */
    public function testWithCustomLevels(): void
    {
        $eventName = $this->event::class;
        $logs = [];

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message) use (&$logs): bool {
                $logs[] = [$level, $message];
                return true;
            });

        $middleware = new LogDomainEventDispatch($this->logger, LogLevel::NOTICE, LogLevel::WARNING);
        $middleware($this->event, function (DomainEventInterface $received) {
            $this->assertSame($this->event, $received);
        });

        $this->assertSame([
            [LogLevel::NOTICE, "Dispatching event {$eventName}."],
            [LogLevel::WARNING, "Dispatched event {$eventName}."],
        ], $logs);
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
