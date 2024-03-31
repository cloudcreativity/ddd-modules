<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\Queue;

use CloudCreativity\Modules\Infrastructure\Queue\EnqueuerInterface;
use CloudCreativity\Modules\Infrastructure\Queue\Queue;
use CloudCreativity\Modules\Infrastructure\Queue\QueueableInterface;
use CloudCreativity\Modules\Infrastructure\Queue\QueueHandlerContainerInterface;
use CloudCreativity\Modules\Infrastructure\Queue\QueueHandlerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Result\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    /**
     * @var QueueHandlerContainerInterface&MockObject
     */
    private QueueHandlerContainerInterface&MockObject $handlers;

    /**
     * @var MockObject&PipeContainerInterface
     */
    private PipeContainerInterface&MockObject $pipes;

    /**
     * @var EnqueuerInterface&MockObject
     */
    private EnqueuerInterface&MockObject $enqueuer;

    /**
     * @var Queue
     */
    private Queue $queue;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->queue = new Queue(
            enqueuer: $this->enqueuer = $this->createMock(EnqueuerInterface::class),
            handlers: $this->handlers = $this->createMock(QueueHandlerContainerInterface::class),
            pipeline: $this->pipes = $this->createMock(PipeContainerInterface::class),
        );
    }

    /**
     * @return void
     */
    public function testItDispatchesQueueable(): void
    {
        $this->willNotQueue();

        $queueable = $this->createMock(QueueableInterface::class);

        $this->handlers
            ->expects($this->once())
            ->method('get')
            ->with($queueable::class)
            ->willReturn($handler = $this->createMock(QueueHandlerInterface::class));

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($queueable))
            ->willReturn($expected = Result::ok());

        $actual = $this->queue->dispatch($queueable);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItDispatchesThroughMiddleware(): void
    {
        $this->willNotQueue();

        $queueable1 = $this->createMock(QueueableInterface::class);
        $queueable2 = $this->createMock(QueueableInterface::class);
        $queueable3 = $this->createMock(QueueableInterface::class);

        $middleware1 = function ($actual, \Closure $next) use ($queueable1, $queueable2) {
            $this->assertSame($queueable1, $actual);
            return $next($queueable2);
        };

        $middleware2 = function ($actual, \Closure $next) use ($queueable2, $queueable3) {
            $this->assertSame($queueable2, $actual);
            return $next($queueable3);
        };

        $this->handlers
            ->expects($this->once())
            ->method('get')
            ->with($queueable1::class)
            ->willReturn($handler = $this->createMock(QueueHandlerInterface::class));

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($queueable3))
            ->willReturn($expected = Result::ok());

        $handler
            ->expects($this->once())
            ->method('middleware')
            ->willReturn(['MySecondMiddleware']);

        $this->pipes
            ->expects($this->once())
            ->method('get')
            ->with('MySecondMiddleware')
            ->willReturn($middleware2);

        $this->queue->through([$middleware1]);
        $actual = $this->queue->dispatch($queueable1);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItQueuesOne(): void
    {
        $this->willNotDispatch();

        $queueable = $this->createMock(QueueableInterface::class);

        $this->enqueuer
            ->expects($this->once())
            ->method('queue')
            ->with($this->identicalTo($queueable));

        $this->queue->push($queueable);
    }

    /**
     * @return void
     */
    public function testItQueuesMany(): void
    {
        $this->willNotDispatch();

        $queueable = [
            $this->createMock(QueueableInterface::class),
            $this->createMock(QueueableInterface::class),
            $this->createMock(QueueableInterface::class),
        ];

        $sequence = [];

        $this->enqueuer
            ->expects($this->exactly(3))
            ->method('queue')
            ->with($this->callback(function ($q) use (&$sequence): bool {
                $sequence[] = $q;
                return true;
            }));

        $this->queue->push($queueable);

        $this->assertSame($queueable, $sequence);
    }

    /**
     * @return void
     */
    private function willNotDispatch(): void
    {
        $this->handlers
            ->expects($this->never())
            ->method($this->anything());

        $this->pipes
            ->expects($this->never())
            ->method($this->anything());
    }

    /**
     * @return void
     */
    private function willNotQueue(): void
    {
        $this->enqueuer
            ->expects($this->never())
            ->method($this->anything());
    }
}
