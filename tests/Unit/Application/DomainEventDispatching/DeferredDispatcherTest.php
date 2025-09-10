<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\DomainEventDispatching;

use Closure;
use CloudCreativity\Modules\Application\DomainEventDispatching\DeferredDispatcher;
use CloudCreativity\Modules\Contracts\Application\DomainEventDispatching\DeferredDispatcher as IDeferredDispatcher;
use CloudCreativity\Modules\Contracts\Application\DomainEventDispatching\ListenerContainer;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeferredDispatcherTest extends TestCase
{
    private ListenerContainer&MockObject $listeners;

    /**
     * @var MockObject&MockObject&PipeContainer
     */
    private MockObject&PipeContainer $middleware;

    private DeferredDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = new DeferredDispatcher(
            listeners: $this->listeners = $this->createMock(ListenerContainer::class),
            middleware: $this->middleware = $this->createMock(PipeContainer::class),
        );
    }

    public function testItDispatchesImmediately(): void
    {
        $sequence = [];
        $event = new TestImmediateDomainEvent();

        $listener1 = $this->createMock(TestListener::class);
        $listener2 = $this->createMock(TestListener::class);
        $listener3 = $this->createMock(TestListener::class);
        $listener4 = $this->createMock(TestListener::class);

        $listener2Closure = static fn (DomainEvent $event) => $listener2->handle($event);

        $this->listeners
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
            ->with($this->identicalTo($event))
            ->willReturnCallback(function () use (&$sequence) {
                $sequence[] = 'Listener1';
            });

        $listener2
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($event))
            ->willReturnCallback(function () use (&$sequence) {
                $sequence[] = 'Listener2';
            });

        $listener3
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($event))
            ->willReturnCallback(function () use (&$sequence) {
                $sequence[] = 'Listener3';
            });

        $listener4
            ->expects($this->never())
            ->method('handle');

        $this->dispatcher->listen($event::class, [
            'Listener1',
            $listener2Closure,
            'Listener3',
        ]);
        $this->dispatcher->listen(TestDomainEvent::class, 'Listener4');

        $this->dispatcher->dispatch($event);

        $this->assertInstanceOf(IDeferredDispatcher::class, $this->dispatcher);
        $this->assertSame($sequence, ['Listener1', 'Listener2', 'Listener3']);
    }

    public function testItFlushesDeferredEvents(): void
    {
        $sequence = [];
        $event1 = new TestDomainEvent();
        $event2 = $this->createMock(DomainEvent::class);

        $listener1 = $this->createMock(TestListener::class);
        $listener2 = $this->createMock(TestListener::class);
        $listener3 = $this->createMock(TestListener::class);
        $listener4 = $this->createMock(TestListener::class);

        $listener2Closure = static fn (DomainEvent $event) => $listener2->handle($event1);

        $this->listeners
            ->expects($this->exactly(3))
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
            ->with($this->identicalTo($event1))
            ->willReturnCallback(function () use (&$sequence) {
                $sequence[] = 'Listener3';
            });

        $listener4
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($event2))
            ->willReturnCallback(function () use (&$sequence) {
                $sequence[] = 'Listener4';
            });

        $this->dispatcher->listen($event1::class, [
            'Listener1',
            $listener2Closure,
            'Listener3',
        ]);
        $this->dispatcher->listen($event2::class, 'Listener4');

        $this->dispatcher->dispatch($event1);
        $this->dispatcher->dispatch($event2);
        $before = $sequence;
        $this->dispatcher->flush();

        $this->assertEmpty($before);
        $this->assertSame($sequence, ['Listener1', 'Listener2', 'Listener3', 'Listener4']);
    }

    public function testItFlushesDeferredEventsIncludingEventsDispatchedByListeners(): void
    {
        $sequence = [];
        $event1 = new TestDomainEvent();
        $event2 = $this->createMock(DomainEvent::class);

        $listener1 = $this->createMock(TestListener::class);
        $listener2 = $this->createMock(TestListener::class);
        $listener3 = $this->createMock(TestListener::class);

        $this->listeners
            ->expects($this->exactly(3))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'Listener1' => $listener1,
                'Listener2' => $listener2,
                'Listener3' => $listener3,
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
            ->willReturnCallback(function () use ($event2, &$sequence) {
                $sequence[] = 'Listener2';
                $this->dispatcher->dispatch($event2);
            });

        $listener3
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($event2))
            ->willReturnCallback(function () use (&$sequence) {
                $sequence[] = 'Listener3';
            });

        $this->dispatcher->listen($event1::class, ['Listener1', 'Listener2']);
        $this->dispatcher->listen($event2::class, 'Listener3');

        $this->dispatcher->dispatch($event1);
        $before = $sequence;
        $this->dispatcher->flush();

        $this->assertEmpty($before);
        $this->assertSame($sequence, ['Listener1', 'Listener2', 'Listener3']);
    }

    public function testItForgetsDeferredEvents(): void
    {
        $sequence = [];
        $deferred = new TestDomainEvent();
        $immediate = new TestImmediateDomainEvent();

        $listener1 = $this->createMock(TestListener::class);
        $listener2 = $this->createMock(TestListener::class);
        $listener3 = $this->createMock(TestListener::class);
        $listener4 = $this->createMock(TestListener::class);

        $this->listeners
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'Listener1' => $listener1,
                'Listener2' => $listener2,
                'Listener3' => $listener3,
                'Listener4' => $listener4,
                default => throw new \RuntimeException('Unexpected name: ' . $name),
            });

        $listener1
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($immediate))
            ->willReturnCallback(function () use (&$sequence) {
                $sequence[] = 'Listener1';
            });

        $listener2
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($immediate))
            ->willReturnCallback(function () use (&$sequence) {
                $sequence[] = 'Listener2';
            });

        $listener3
            ->expects($this->never())
            ->method('handle');

        $listener4
            ->expects($this->never())
            ->method('handle');

        $this->dispatcher->listen($immediate::class, ['Listener1', 'Listener2']);
        $this->dispatcher->listen($deferred::class, ['Listener3', 'Listener4']);

        $this->dispatcher->dispatch($deferred);
        $step1 = $sequence;
        $this->dispatcher->dispatch($immediate);
        $step2 = $sequence;
        $this->dispatcher->forget();
        $this->dispatcher->flush();
        $step3 = $sequence;

        $this->assertEmpty($step1);
        $this->assertSame($step2, ['Listener1', 'Listener2']);
        $this->assertSame($step3, ['Listener1', 'Listener2']);
    }

    public function testItForgetsDeferredEventsAfterException(): void
    {
        $sequence = [];
        $event1 = new TestDomainEvent();
        $event2 = $this->createMock(DomainEvent::class);
        $expected = new \LogicException('Boom!');

        $listener1 = $this->createMock(TestListener::class);
        $listener2 = $this->createMock(TestListener::class);
        $listener3 = $this->createMock(TestListener::class);
        $listener4 = $this->createMock(TestListener::class);

        $this->listeners
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'Listener1' => $listener1,
                'Listener2' => $listener2,
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
            ->willReturnCallback(function () use ($expected, &$sequence) {
                $sequence[] = 'Listener2';
                throw $expected;
            });

        $listener3
            ->expects($this->never())
            ->method('handle');

        $listener4
            ->expects($this->never())
            ->method('handle');

        $this->dispatcher->listen($event1::class, ['Listener1', 'Listener2']);
        $this->dispatcher->listen($event2::class, ['Listener3', 'Listener4']);
        $this->dispatcher->dispatch($event1);
        $this->dispatcher->dispatch($event2);

        try {
            $this->dispatcher->flush();
            $this->fail('Expected exception not thrown.');
        } catch (\LogicException $actual) {
            $this->assertSame($expected, $actual);
        }

        $this->dispatcher->flush(); // flush again, not expecting any events to be triggered.
    }

    public function testNoListeners(): void
    {
        $event = $this->createMock(DomainEvent::class);
        $this->listeners->expects($this->never())->method($this->anything());
        $this->dispatcher->dispatch($event);
    }

    public function testItDispatchesThroughMiddleware(): void
    {
        $event1 = new TestImmediateDomainEvent();
        $event2 = new TestImmediateDomainEvent();
        $event3 = new TestImmediateDomainEvent();

        $a = function ($actual, Closure $next) use ($event1, $event2): DomainEvent {
            $this->assertSame($event1, $actual);
            /** @phpstan-ignore-next-line */
            return $next($event2);
        };

        $b = function ($actual, Closure $next) use ($event2, $event3): DomainEvent {
            $this->assertSame($event2, $actual);
            /** @phpstan-ignore-next-line */
            return $next($event3);
        };

        $this->middleware
            ->expects($this->once())
            ->method('get')
            ->with('Middleware2')
            ->willReturn($b);

        $listener = $this->createMock(TestListener::class);

        $listener
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($event3));

        $this->listeners
            ->expects($this->once())
            ->method('get')
            ->with('MyListener')
            ->willReturn($listener);

        $this->dispatcher->through([$a, 'Middleware2']);
        $this->dispatcher->listen($event3::class, 'MyListener');
        $this->dispatcher->dispatch($event1);
    }

    public function testListenerDoesNotHaveHandleMethod(): void
    {
        $event = new TestImmediateDomainEvent();

        $this->listeners
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
