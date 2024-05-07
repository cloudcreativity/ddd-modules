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

use CloudCreativity\Modules\Contracts\Application\Messages\IntegrationEvent;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Infrastructure\OutboundEventBus\ClosurePublisher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClosurePublisherTest extends TestCase
{
    /**
     * @var MockObject&PipeContainer
     */
    private PipeContainer&MockObject $middleware;

    /**
     * @var array<IntegrationEvent>
     */
    private array $actual = [];

    /**
     * @var ClosurePublisher
     */
    private ClosurePublisher $publisher;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->publisher = new ClosurePublisher(
            function (IntegrationEvent $event): void {
                $this->actual[] = $event;
            },
            $this->middleware = $this->createMock(PipeContainer::class),
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->publisher, $this->middleware, $this->actual);
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $event = $this->createMock(IntegrationEvent::class);

        $this->publisher->publish($event);

        $this->assertSame([$event], $this->actual);
    }

    /**
     * @return void
     */
    public function testWithMiddleware(): void
    {
        $event1 = $this->createMock(IntegrationEvent::class);
        $event2 = $this->createMock(IntegrationEvent::class);
        $event3 = $this->createMock(IntegrationEvent::class);
        $event4 = $this->createMock(IntegrationEvent::class);

        $middleware1 = function ($event, \Closure $next) use ($event1, $event2) {
            $this->assertSame($event1, $event);
            return $next($event2);
        };

        $middleware2 = function ($event, \Closure $next) use ($event2, $event3) {
            $this->assertSame($event2, $event);
            return $next($event3);
        };

        $middleware3 = function ($event, \Closure $next) use ($event3, $event4) {
            $this->assertSame($event3, $event);
            return $next($event4);
        };

        $this->middleware
            ->expects($this->once())
            ->method('get')
            ->with('MySecondMiddleware')
            ->willReturn($middleware2);

        $this->publisher->through([
            $middleware1,
            'MySecondMiddleware',
            $middleware3,
        ]);

        $this->publisher->publish($event1);

        $this->assertSame([$event4], $this->actual);
    }


    /**
     * @return void
     */
    public function testWithAlternativeHandlers(): void
    {
        $expected = new TestOutboundEvent();
        $mock = $this->createMock(IntegrationEvent::class);
        $actual = null;

        $this->publisher->bind($mock::class, function (): never {
            $this->fail('Not expecting this closure to be called.');
        });

        $this->publisher->bind(
            TestOutboundEvent::class,
            function (TestOutboundEvent $in) use (&$actual) {
                $actual = $in;
            },
        );

        $this->publisher->publish($expected);

        $this->assertEmpty($this->actual);
        $this->assertSame($expected, $actual);
    }
}
