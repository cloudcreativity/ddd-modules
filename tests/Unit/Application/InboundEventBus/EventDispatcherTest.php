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
use CloudCreativity\Modules\Contracts\Application\InboundEventBus\EventHandler;
use CloudCreativity\Modules\Contracts\Application\InboundEventBus\EventHandlerContainer;
use CloudCreativity\Modules\Contracts\Application\Messages\IntegrationEvent;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventDispatcherTest extends TestCase
{
    /**
     * @var EventHandlerContainer&MockObject
     */
    private EventHandlerContainer&MockObject $handlers;

    /**
     * @var PipeContainer&MockObject
     */
    private PipeContainer&MockObject $middleware;

    /**
     * @var EventDispatcher
     */
    private EventDispatcher $dispatcher;

    /**
     * @var array<string>
     */
    private array $sequence = [];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = new EventDispatcher(
            handlers: $this->handlers = $this->createMock(EventHandlerContainer::class),
            middleware: $this->middleware = $this->createMock(PipeContainer::class),
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->handlers, $this->middleware, $this->dispatcher, $this->sequence);
        parent::tearDown();
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
            ->willReturn($handler = $this->createMock(EventHandler::class));

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
        $handler = $this->createMock(EventHandler::class);

        $middleware1 = function (TestInboundEvent $event, \Closure $next) use ($event1, $event2): void {
            $this->assertSame($event1, $event);
            $this->sequence[] = 'before1';
            $next($event2);
            $this->sequence[] = 'after1';
        };

        $middleware2 = function (TestInboundEvent $event, \Closure $next) use ($event2, $event3): void {
            $this->assertSame($event2, $event);
            $this->sequence[] = 'before2';
            $next($event3);
            $this->sequence[] = 'after2';
        };

        $middleware3 = function (TestInboundEvent $event, \Closure $next) use ($event3, $event4): void {
            $this->assertSame($event3, $event);
            $this->sequence[] = 'before3';
            $next($event4);
            $this->sequence[] = 'after3';
        };

        $this->handlers
            ->method('get')
            ->with($event1::class)
            ->willReturnCallback(function () use ($handler) {
                $this->assertSame(['before1'], $this->sequence);
                return $handler;
            });

        $this->middleware
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'MyFirstMiddleware' => $middleware1,
                'MySecondMiddleware' => $middleware2,
                default => $this->fail('Unexpected middleware: ' . $name),
            });

        $handler
            ->expects($this->once())
            ->method('middleware')
            ->willReturn(['MySecondMiddleware', $middleware3]);

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($event4));

        $this->dispatcher->through(['MyFirstMiddleware']);
        $this->dispatcher->dispatch($event1);

        $this->assertSame([
            'before1',
            'before2',
            'before3',
            'after3',
            'after2',
            'after1',
        ], $this->sequence);
    }
}
