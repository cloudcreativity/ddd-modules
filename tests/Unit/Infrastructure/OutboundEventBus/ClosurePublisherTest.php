<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\OutboundEventBus;

use CloudCreativity\Modules\Contracts\Messaging\IntegrationEvent;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer as IPipeContainer;
use CloudCreativity\Modules\Infrastructure\OutboundEventBus\ClosurePublisher;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class ClosurePublisherTest extends TestCase
{
    /**
     * @var array<IntegrationEvent>
     */
    private array $actual = [];

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->actual);
    }

    public function test(): void
    {
        $event = $this->createStub(IntegrationEvent::class);

        $publisher = $this->createPublisher();
        $publisher->publish($event);

        $this->assertSame([$event], $this->actual);
    }

    public function testWithMiddleware(): void
    {
        $event1 = $this->createStub(IntegrationEvent::class);
        $event2 = $this->createStub(IntegrationEvent::class);
        $event3 = $this->createStub(IntegrationEvent::class);
        $event4 = $this->createStub(IntegrationEvent::class);

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

        $middleware = $this->createMock(IPipeContainer::class);
        $middleware
            ->expects($this->once())
            ->method('get')
            ->with('MySecondMiddleware')
            ->willReturn($middleware2);

        $publisher = $this->createPublisher($middleware);
        $publisher->through([
            $middleware1,
            'MySecondMiddleware',
            $middleware3,
        ]);

        $publisher->publish($event1);

        $this->assertSame([$event4], $this->actual);
    }

    public function testWithMiddlewareViaPsrContainer(): void
    {
        $event1 = $this->createStub(IntegrationEvent::class);
        $event2 = $this->createStub(IntegrationEvent::class);
        $event3 = $this->createStub(IntegrationEvent::class);

        $middleware1 = function ($event, \Closure $next) use ($event1, $event2) {
            $this->assertSame($event1, $event);
            return $next($event2);
        };

        $middleware2 = function ($event, \Closure $next) use ($event2, $event3) {
            $this->assertSame($event2, $event);
            return $next($event3);
        };

        $psrContainer = $this->createMock(ContainerInterface::class);
        $psrContainer
            ->expects($this->once())
            ->method('get')
            ->with('MySecondMiddleware')
            ->willReturn($middleware2);

        $publisher = $this->createPublisher($psrContainer);
        $publisher->through([
            $middleware1,
            'MySecondMiddleware',
        ]);

        $publisher->publish($event1);

        $this->assertSame([$event3], $this->actual);
    }


    public function testWithAlternativeHandlers(): void
    {
        $expected = new class () extends TestOutboundEvent {};
        $stub = $this->createStub(IntegrationEvent::class);
        $actual = null;

        $publisher = $this->createPublisher();

        $publisher->bind($stub::class, function (): never {
            $this->fail('Not expecting this closure to be called.');
        });

        $publisher->bind(
            $expected::class,
            function (TestOutboundEvent $in) use (&$actual) {
                $actual = $in;
            },
        );

        $publisher->publish($expected);

        $this->assertEmpty($this->actual);
        $this->assertSame($expected, $actual);
    }

    private function createPublisher(ContainerInterface|IPipeContainer|null $middleware = null): ClosurePublisher
    {
        return new class (
            function (IntegrationEvent $event): void {
                $this->actual[] = $event;
            },
            $middleware,
        ) extends ClosurePublisher {};
    }
}
