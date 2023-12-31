<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\DomainEventDispatching;

use Closure;
use CloudCreativity\Modules\Domain\Events\DomainEventInterface;
use CloudCreativity\Modules\Infrastructure\DomainEventDispatching\DispatchAfterCommit;
use CloudCreativity\Modules\Infrastructure\DomainEventDispatching\DispatchBeforeCommit;
use CloudCreativity\Modules\Infrastructure\DomainEventDispatching\UnitOfWorkAwareDispatcher;
use CloudCreativity\Modules\Infrastructure\DomainEventDispatching\ListenerContainerInterface;
use CloudCreativity\Modules\Infrastructure\Persistence\UnitOfWorkManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UnitOfWorkAwareDispatcherTest extends TestCase
{
    /**
     * @var ListenerContainerInterface&MockObject
     */
    private ListenerContainerInterface $container;

    /**
     * @var UnitOfWorkManagerInterface&MockObject
     */
    private UnitOfWorkManagerInterface $unitOfWorkManager;

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
            $this->container = $this->createMock(ListenerContainerInterface::class),
            $this->unitOfWorkManager = $this->createMock(UnitOfWorkManagerInterface::class),
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

        $listener2Closure = static fn ($event) => $listener2->handle($event);

        $this->container
            ->expects($this->exactly(5))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'Listener1' => $listener1,
                'Listener3' => $listener3,
                'Listener4' => $listener4,
                'Listener5' => $listener5,
                'Listener6' => $listener6,
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

        $this->dispatcher->dispatch($event);
        $this->assertSame($sequence, ['Listener1', 'Listener2', 'Listener3']);
    }

    /**
     * @return void
     */
    public function testItDoesNotDispatchImmediately(): void
    {
        $event = $this->createMock(DomainEventInterface::class);

        $listener1 = $this->createMock(TestListener::class);
        $listener2 = $this->createMock(TestListener::class);

        $this->container
            ->expects($this->never())
            ->method('get');

        $listener1
            ->expects($this->never())
            ->method('handle');

        $listener2
            ->expects($this->never())
            ->method('handle');

        $this->unitOfWorkManager
            ->expects($this->never())
            ->method('beforeCommit');

        $this->unitOfWorkManager
            ->expects($this->exactly(1))
            ->method('afterCommit'); // we'll prove this works in the subsequent test

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
    public function testItDispatchesEventInAfterCommitCallback(): void
    {
        $event = new TestDomainEvent();

        $listener1 = $this->createMock(TestListener::class);
        $listener2 = $this->createMock(TestListener::class);

        $this->container
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
            ->expects($this->exactly(1))
            ->method('afterCommit')
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

        $this->container
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

        $this->container
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
        $event = $this->createMock(DomainEventInterface::class);
        $this->container->expects($this->never())->method($this->anything());
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

        $this->container
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
