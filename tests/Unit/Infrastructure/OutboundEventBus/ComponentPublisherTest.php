<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

        $middleware1 = function ($actual, Closure $next) use ($event1, $event2) {
            $this->assertSame($event1, $actual);
            return $next($event2);
        };

        $middleware2 = function ($actual, Closure $next) use ($event2, $event3) {
            $this->assertSame($event2, $actual);
            return $next($event3);
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
            ->willReturn($handler);

        $this->publisher->through([$middleware1, 'Middleware2']);
        $this->publisher->publish($event1);
    }
}
