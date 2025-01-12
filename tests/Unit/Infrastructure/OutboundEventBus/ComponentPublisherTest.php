<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\OutboundEventBus;

use Closure;
use CloudCreativity\Modules\Contracts\Infrastructure\OutboundEventBus\PublisherHandler;
use CloudCreativity\Modules\Contracts\Infrastructure\OutboundEventBus\PublisherHandlerContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Infrastructure\OutboundEventBus\ComponentPublisher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ComponentPublisherTest extends TestCase
{
    /**
     * @var PublisherHandlerContainer&MockObject
     */
    private PublisherHandlerContainer $handlers;

    /**
     * @var MockObject&PipeContainer
     */
    private PipeContainer&MockObject $middleware;

    /**
     * @var ComponentPublisher
     */
    private ComponentPublisher $publisher;

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

        $this->publisher = new ComponentPublisher(
            handlers: $this->handlers = $this->createMock(PublisherHandlerContainer::class),
            middleware: $this->middleware = $this->createMock(PipeContainer::class),
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->publisher, $this->handlers, $this->middleware);
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testPublish(): void
    {
        $event = new TestOutboundEvent();

        $this->handlers
            ->expects($this->once())
            ->method('get')
            ->with($event::class)
            ->willReturn($handler = $this->createMock(PublisherHandler::class));

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
        $event1 = new TestOutboundEvent();
        $event2 = new TestOutboundEvent();
        $event3 = new TestOutboundEvent();

        $middleware1 = function ($actual, Closure $next) use ($event1, $event2): void {
            $this->assertSame($event1, $actual);
            $this->sequence[] = 'before1';
            $next($event2);
            $this->sequence[] = 'after1';
        };

        $middleware2 = function ($actual, Closure $next) use ($event2, $event3): void {
            $this->assertSame($event2, $actual);
            $this->sequence[] = 'before2';
            $next($event3);
            $this->sequence[] = 'after2';
        };

        $this->middleware
            ->expects($this->once())
            ->method('get')
            ->with('Middleware2')
            ->willReturn($middleware2);

        $handler = $this->createMock(PublisherHandler::class);

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($event3));

        $this->handlers
            ->expects($this->once())
            ->method('get')
            ->with($event1::class)
            ->willReturnCallback(function () use ($handler) {
                $this->assertSame(['before1', 'before2'], $this->sequence);
                return $handler;
            });

        $this->publisher->through([$middleware1, 'Middleware2']);
        $this->publisher->publish($event1);

        $this->assertSame(['before1', 'before2', 'after2', 'after1'], $this->sequence);
    }
}
