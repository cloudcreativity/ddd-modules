<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\DomainEventDispatching;

use Closure;
use CloudCreativity\Modules\Domain\Events\DomainEventInterface;
use CloudCreativity\Modules\Infrastructure\DomainEventDispatching\Dispatcher;
use CloudCreativity\Modules\Infrastructure\DomainEventDispatching\ListenerContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DispatcherTest extends TestCase
{
    /**
     * @var ListenerContainerInterface&MockObject
     */
    private ListenerContainerInterface $container;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = new Dispatcher(
            $this->container = $this->createMock(ListenerContainerInterface::class),
        );
    }

    /**
     * @return void
     */
    public function testItDispatchesImmediately(): void
    {
        $sequence = [];
        $event1 = new TestImmediateDomainEvent();
        $event2 = new TestDomainEvent();

        $listener1 = $this->createMock(TestListener::class);
        $listener2 = $this->createMock(TestListener::class);
        $listener3 = $this->createMock(TestListener::class);
        $listener4 = $this->createMock(TestListener::class);

        $listener2Closure = static fn ($event) => $listener2->handle($event);

        $this->container
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'Listener1' => $listener1,
                'Listener3' => $listener3,
                'Listener4' => $listener4,
                default => throw new \RuntimeException('Unexpected name: ' . $name),
            });

        $listener1
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($event1))
            ->willReturnCallback(function () use (&$sequence) {
                $sequence[] = 'Listener1';
            });

        $listener2
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($event1))
            ->willReturnCallback(function () use (&$sequence) {
                $sequence[] = 'Listener2';
            });

        $listener3
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($event2))
            ->willReturnCallback(function () use (&$sequence) {
                $sequence[] = 'Listener3';
            });

        $listener4
            ->expects($this->never())
            ->method('handle');

        $this->dispatcher->listen($event1::class, ['Listener1', $listener2Closure]);
        $this->dispatcher->listen($event2::class, ['Listener3']);
        $this->dispatcher->listen($this->createMock(TestDomainEvent::class)::class, 'Listener4');

        $this->dispatcher->dispatch($event1);
        $this->dispatcher->dispatch($event2);
        $this->assertSame($sequence, ['Listener1', 'Listener2', 'Listener3']);
    }

    /**
     * @return void
     */
    public function testNoListeners(): void
    {
        $event = $this->createMock(DomainEventInterface::class);
        $this->container->expects($this->never())->method($this->anything());
        $this->dispatcher->dispatch($event);
    }

    /**
     * @return void
     */
    public function testItDispatchesThroughMiddleware(): void
    {
        $event1 = new TestDomainEvent();
        $event2 = new TestDomainEvent();
        $event3 = new TestImmediateDomainEvent();

        $a = function ($actual, Closure $next) use ($event1, $event2): DomainEventInterface {
            $this->assertSame($event1, $actual);
            return $next($event2);
        };

        $b = function ($actual, Closure $next) use ($event2, $event3): DomainEventInterface {
            $this->assertSame($event2, $actual);
            return $next($event3);
        };

        $listener = $this->createMock(TestListener::class);

        $listener
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($event3));

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('MyListener')
            ->willReturn($listener);

        $this->dispatcher->through([$a, $b]);
        $this->dispatcher->listen($event3::class, 'MyListener');
        $this->dispatcher->dispatch($event1);
    }

    /**
     * @return void
     */
    public function testListenerDoesNotHaveHandleMethod(): void
    {
        $event = new TestImmediateDomainEvent();

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('Listener1')
            ->willReturn(new \DateTime());

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Listener "DateTime" is not an object with a handle method or a closure.');

        $this->dispatcher->listen($event::class, 'Listener1');
        $this->dispatcher->dispatch($event);
    }
}
