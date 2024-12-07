<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\DomainEventDispatching\Middleware;

use CloudCreativity\Modules\Application\DomainEventDispatching\Middleware\LogDomainEventDispatch;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;
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
     * @var DomainEvent
     */
    private DomainEvent $event;

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
        $this->event = new class () implements DomainEvent {
            public string $foo = 'foo';
            public string $bar = 'bar';

            public function getOccurredAt(): DateTimeImmutable
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
        $middleware($this->event, function (DomainEvent $received) {
            $this->assertSame($this->event, $received);
        });

        $this->assertSame([
            [LogLevel::DEBUG, "Dispatching domain event {$eventName}.", []],
            [LogLevel::INFO, "Dispatched domain event {$eventName}.", []],
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
        $middleware($this->event, function (DomainEvent $received) {
            $this->assertSame($this->event, $received);
        });

        $this->assertSame([
            [LogLevel::NOTICE, "Dispatching domain event {$eventName}."],
            [LogLevel::WARNING, "Dispatched domain event {$eventName}."],
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
            ->with(LogLevel::DEBUG, "Dispatching domain event {$eventName}.");

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
