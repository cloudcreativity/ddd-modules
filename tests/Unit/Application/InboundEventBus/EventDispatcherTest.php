<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\InboundEventBus;

use CloudCreativity\Modules\Application\InboundEventBus\EventDispatcher;
use CloudCreativity\Modules\Application\InboundEventBus\EventHandlerContainerInterface;
use CloudCreativity\Modules\Application\InboundEventBus\EventHandlerInterface;
use CloudCreativity\Modules\Contracts\Application\Messages\IntegrationEvent;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventDispatcherTest extends TestCase
{
    /**
     * @var EventHandlerContainerInterface&MockObject
     */
    private EventHandlerContainerInterface&MockObject $handlers;

    /**
     * @var PipeContainer&MockObject
     */
    private PipeContainer&MockObject $middleware;

    /**
     * @var EventDispatcher
     */
    private EventDispatcher $dispatcher;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = new EventDispatcher(
            handlers: $this->handlers = $this->createMock(EventHandlerContainerInterface::class),
            middleware: $this->middleware = $this->createMock(PipeContainer::class),
        );
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $event = $this->createMock(IntegrationEvent::class);

        $this->handlers
            ->expects($this->once())
            ->method('get')
            ->with($event::class)
            ->willReturn($handler = $this->createMock(EventHandlerInterface::class));

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($event));

        $this->dispatcher->dispatch($event);
    }

    /**
     * @return void
     */
    public function testWithMiddleware(): void
    {
        $event1 = new TestInboundEvent();
        $event2 = new TestInboundEvent();
        $event3 = new TestInboundEvent();
        $event4 = new TestInboundEvent();

        $middleware1 = function (TestInboundEvent $event, \Closure $next) use ($event1, $event2) {
            $this->assertSame($event1, $event);
            return $next($event2);
        };

        $middleware2 = function (TestInboundEvent $event, \Closure $next) use ($event2, $event3) {
            $this->assertSame($event2, $event);
            return $next($event3);
        };

        $middleware3 = function (TestInboundEvent $event, \Closure $next) use ($event3, $event4) {
            $this->assertSame($event3, $event);
            return $next($event4);
        };

        $this->handlers
            ->method('get')
            ->with($event1::class)
            ->willReturn($handler = $this->createMock(EventHandlerInterface::class));

        $this->middleware
            ->expects($this->once())
            ->method('get')
            ->with('MySecondMiddleware')
            ->willReturn($middleware2);

        $handler
            ->expects($this->once())
            ->method('middleware')
            ->willReturn(['MySecondMiddleware', $middleware3]);

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($event4));

        $this->dispatcher->through([$middleware1]);
        $this->dispatcher->dispatch($event1);
    }
}
