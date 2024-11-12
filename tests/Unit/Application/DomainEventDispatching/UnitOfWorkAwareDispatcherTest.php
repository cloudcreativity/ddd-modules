<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\DomainEventDispatching;

use Closure;
use CloudCreativity\Modules\Application\DomainEventDispatching\UnitOfWorkAwareDispatcher;
use CloudCreativity\Modules\Contracts\Application\DomainEventDispatching\ListenerContainer;
use CloudCreativity\Modules\Contracts\Application\UnitOfWork\DispatchAfterCommit;
use CloudCreativity\Modules\Contracts\Application\UnitOfWork\DispatchBeforeCommit;
use CloudCreativity\Modules\Contracts\Application\UnitOfWork\UnitOfWorkManager;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEventDispatcher;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UnitOfWorkAwareDispatcherTest extends TestCase
{
    /**
     * @var ListenerContainer&MockObject
     */
    private ListenerContainer&MockObject $listeners;

    /**
     * @var UnitOfWorkManager&MockObject
     */
    private UnitOfWorkManager $unitOfWorkManager;

    /**
     * @var MockObject&PipeContainer
     */
    private PipeContainer&MockObject $middleware;

    /**
     * @var UnitOfWorkAwareDispatcher
     */
    private UnitOfWorkAwareDispatcher $dispatcher;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = new UnitOfWorkAwareDispatcher(
            unitOfWorkManager: $this->unitOfWorkManager = $this->createMock(UnitOfWorkManager::class),
            listeners: $this->listeners = $this->createMock(ListenerContainer::class),
            middleware: $this->middleware = $this->createMock(PipeContainer::class),
        );
    }

    /**
     * @return void
     */
    public function testItDispatchesImmediately(): void
    {
        $sequence = [];
        $event = new TestImmediateDomainEvent();

        $listener1 = $this->createMock(TestListener::class);
        $listener2 = $this->createMock(TestListener::class);
        $listener3 = $this->createMock(TestListener::class);
        $listener4 = $this->createMock(TestListenerBeforeCommit::class);
        $listener5 = $this->createMock(TestListenerAfterCommit::class);
        $listener6 = $this->createMock(TestListenerAfterCommit::class);
        $listener7 = $this->createMock(TestListener::class);

        $listener2Closure = static fn (DomainEvent $event) => $listener2->handle($event);

        $this->listeners
            ->expects($this->exactly(5))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'Listener1' => $listener1,
                'Listener3' => $listener3,
                'Listener4' => $listener4,
                'Listener5' => $listener5,
                'Listener6' => $listener6,
                'Listener7' => $listener7,
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

        $listener5
            ->expects($this->never())
            ->method('handle');

        $listener6
            ->expects($this->never())
            ->method('handle');

        $listener7
            ->expects($this->never())
            ->method('handle');

        $this->unitOfWorkManager
            ->expects($this->once())
            ->method('beforeCommit');

        $this->unitOfWorkManager
            ->expects($this->exactly(2))
            ->method('afterCommit');

        $this->dispatcher->listen($event::class, [
            'Listener1',
            $listener2Closure,
            'Listener3',
            'Listener4',
            'Listener5',
            'Listener6',
        ]);
        $this->dispatcher->listen(TestDomainEvent::class, 'Listener7');

        $this->dispatcher->dispatch($event);

        $this->assertInstanceOf(DomainEventDispatcher::class, $this->dispatcher);
        $this->assertSame($sequence, ['Listener1', 'Listener2', 'Listener3']);
    }

    /**
     * @return void
     */
    public function testItDoesNotDispatchImmediately(): void
    {
        $event = $this->createMock(DomainEvent::class);

        $listener1 = $this->createMock(TestListener::class);
        $listener2 = $this->createMock(TestListener::class);

        $this->listeners
            ->expects($this->never())
            ->method('get');

        $listener1
            ->expects($this->never())
            ->method('handle');

        $listener2
            ->expects($this->never())
            ->method('handle');

        $this->unitOfWorkManager
            ->expects($this->once())
            ->method('beforeCommit');  // we'll prove this works in the subsequent test

        $this->unitOfWorkManager
            ->expects($this->never())
            ->method('afterCommit');

        $this->dispatcher->listen($event::class, [
            'Listener1',
            'Listener2',
        ]);

        $this->dispatcher->dispatch($event);
    }

    /**
     * @return void
     * @depends testItDoesNotDispatchImmediately
     */
    public function testItDispatchesEventInBeforeCommitCallback(): void
    {
        $event = new TestDomainEvent();

        $listener1 = $this->createMock(TestListener::class);
        $listener2 = $this->createMock(TestListener::class);

        $this->listeners
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(static fn (string $name) => match ($name) {
                'Listener1' => $listener1,
                'Listener2' => $listener2,
                default => throw new \RuntimeException('Unexpected name: ' . $name),
            });

        $listener1
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($event));

        $listener2
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($event));

        $this->unitOfWorkManager
            ->expects($this->once())
            ->method('beforeCommit')
            ->with($this->callback(function (Closure $callback): bool {
                $callback();
                return true;
            }));

        $this->dispatcher->listen($event::class, [
            'Listener1',
            'Listener2',
        ]);

        $this->dispatcher->dispatch($event);
    }

    /**
     * @return void
     */
    public function testBeforeCommitListener(): void
    {
        $event = new TestImmediateDomainEvent();
        $listener = $this->createMock(TestListenerBeforeCommit::class);

        $listener
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($event));

        $this->unitOfWorkManager
            ->expects($this->once())
            ->method('beforeCommit')
            ->willReturnCallback(function (Closure $callback) {
                $callback();
            });

        $this->unitOfWorkManager
            ->expects($this->never())
            ->method('afterCommit');

        $this->listeners
            ->expects($this->once())
            ->method('get')
            ->with('BeforeCommitListener')
            ->willReturn($listener);

        $this->dispatcher->listen($event::class, 'BeforeCommitListener');
        $this->dispatcher->dispatch($event);
    }

    /**
     * @return void
     */
    public function testAfterCommitListener(): void
    {
        $event = new TestImmediateDomainEvent();
        $listener = $this->createMock(TestListenerAfterCommit::class);

        $listener
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($event));

        $this->unitOfWorkManager
            ->expects($this->once())
            ->method('afterCommit')
            ->willReturnCallback(function (Closure $callback) {
                $callback();
            });

        $this->unitOfWorkManager
            ->expects($this->never())
            ->method('beforeCommit');

        $this->listeners
            ->expects($this->once())
            ->method('get')
            ->with('AfterCommitListener')
            ->willReturn($listener);

        $this->dispatcher->listen($event::class, 'AfterCommitListener');
        $this->dispatcher->dispatch($event);
    }

    /**
     * @return void
     */
    public function testNoListeners(): void
    {
        $event = $this->createMock(DomainEvent::class);
        $this->listeners->expects($this->never())->method($this->anything());
        $this->dispatcher->dispatch($event);
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function testListenerCannotImplementBothBeforeAndAfterCommit(): void
    {
        $listener = new class () implements DispatchBeforeCommit, DispatchAfterCommit {
            public function handle(): void
            {
                // no-op
            }
        };

        $this->listeners
            ->method('get')
            ->with('InvalidListener')
            ->willReturn($listener);

        $event = new TestImmediateDomainEvent();
        $this->dispatcher->listen($event::class, 'InvalidListener');

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('cannot be dispatched both before and after a unit of work is committed.');

        $this->dispatcher->dispatch($event);
    }
}
