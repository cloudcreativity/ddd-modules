<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\EventBus\Outbound;

use Closure;
use CloudCreativity\Modules\EventBus\IntegrationEventHandlerContainerInterface;
use CloudCreativity\Modules\EventBus\IntegrationEventHandlerInterface;
use CloudCreativity\Modules\EventBus\Outbound\Publisher;
use CloudCreativity\Modules\Tests\Unit\EventBus\TestIntegrationEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PublisherTest extends TestCase
{
    /**
     * @var IntegrationEventHandlerContainerInterface&MockObject
     */
    private IntegrationEventHandlerContainerInterface $container;

    /**
     * @var Publisher
     */
    private Publisher $publisher;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(IntegrationEventHandlerContainerInterface::class);
        $this->publisher = new Publisher($this->container);
    }

    /**
     * @return void
     */
    public function testPublish(): void
    {
        $event = new TestIntegrationEvent();

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with($event::class)
            ->willReturn($handler = $this->createMock(IntegrationEventHandlerInterface::class));

        $handler
            ->expects($this->once())
            ->method('middleware')
            ->willReturn([]);

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($event));

        $this->publisher->publish($event);
    }

    /**
     * @return void
     */
    public function testPublishWithMiddleware(): void
    {
        $event1 = new TestIntegrationEvent();
        $event2 = new TestIntegrationEvent();
        $event3 = new TestIntegrationEvent();

        $middleware1 = function ($actual, Closure $next) use ($event1, $event2) {
            $this->assertSame($event1, $actual);
            return $next($event2);
        };

        $middleware2 = function ($actual, Closure $next) use ($event2, $event3) {
            $this->assertSame($event2, $actual);
            return $next($event3);
        };

        $handler = $this->createMock(IntegrationEventHandlerInterface::class);

        $handler
            ->expects($this->once())
            ->method('middleware')
            ->willReturn([$middleware2]);

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($event3));

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with($event1::class)
            ->willReturn($handler);

        $this->publisher->through([$middleware1]);
        $this->publisher->publish($event1);
    }
}
