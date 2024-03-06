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

use Closure;
use CloudCreativity\Modules\Infrastructure\Queue\Queue;
use CloudCreativity\Modules\Infrastructure\Queue\QueueableBatch;
use CloudCreativity\Modules\Infrastructure\Queue\QueueableInterface;
use CloudCreativity\Modules\Infrastructure\Queue\QueueHandlerContainerInterface;
use CloudCreativity\Modules\Infrastructure\Queue\QueueHandlerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    /**
     * @var QueueHandlerContainerInterface&MockObject
     */
    private QueueHandlerContainerInterface $container;

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

        $this->container = $this->createMock(QueueHandlerContainerInterface::class);
        $this->queue = new Queue($this->container);
    }

    /**
     * @return void
     */
    public function testPush(): void
    {
        $message = $this->createMock(QueueableInterface::class);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with($message::class)
            ->willReturn($handler = $this->createMock(QueueHandlerInterface::class));

        $handler
            ->expects($this->once())
            ->method('withBatch')
            ->with($this->equalTo(new QueueableBatch($message)));

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($message));

        $this->queue->push($message);
    }

    /**
     * @return void
     */
    public function testPushWithMiddleware(): void
    {
        $message1 = $this->createMock(QueueableInterface::class);
        $message2 = $this->createMock(QueueableInterface::class);
        $message3 = $this->createMock(QueueableInterface::class);

        $middleware1 = function ($actual, \Closure $next) use ($message1, $message2) {
            $this->assertSame($message1, $actual);
            return $next($message2);
        };

        $middleware2 = function ($actual, \Closure $next) use ($message2, $message3) {
            $this->assertSame($message2, $actual);
            return $next($message3);
        };

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with($message1::class)
            ->willReturn($handler = $this->createMock(QueueHandlerInterface::class));

        $handler
            ->expects($this->once())
            ->method('withBatch')
            ->with($this->equalTo(new QueueableBatch($message1)));

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($message3));

        $handler
            ->expects($this->once())
            ->method('middleware')
            ->willReturn([$middleware2]);

        $this->queue->through([$middleware1]);
        $this->queue->push($message1);
    }

    /**
     * @return void
     */
    public function testPushBatch(): void
    {
        $message1 = $this->createMock(QueueableInterface::class);
        $message2 = $this->createMock(QueueableInterface::class);
        $batch = new QueueableBatch($message1, $message2);
        $invoked = [];

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with($message1::class)
            ->willReturn($handler = $this->createMock(QueueHandlerInterface::class));

        $handler
            ->expects($this->once())
            ->method('withBatch')
            ->with($this->identicalTo($batch));

        $handler
            ->expects($this->once())
            ->method('middleware')
            ->willReturn([]);

        $handler
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->willReturnCallback(function ($message) use (&$invoked): bool {
                $invoked[] = $message;
                return true;
            });

        $this->queue->pushBatch($batch);
        $this->assertSame([$message1, $message2], $invoked);
    }

    /**
     * @return void
     */
    public function testPushBatchWithMiddleware(): void
    {
        $message1 = $this->createMock(QueueableInterface::class);
        $message2 = $this->createMock(QueueableInterface::class);
        $actual = [];
        $batch = new QueueableBatch($message1, $message2);
        $invoked = [];

        $middleware1 = function ($job, Closure $next) use (&$actual) {
            $actual['m1'] ??= [];
            $actual['m1'][] = $job;
            return $next($job);
        };

        $middleware2 = function ($job, Closure $next) use (&$actual) {
            $actual['m2'] ??= [];
            $actual['m2'][] = $job;
            return $next($job);
        };

        $this->container
            ->method('get')
            ->with($message1::class)
            ->willReturn($handler = $this->createMock(QueueHandlerInterface::class));

        $handler
            ->expects($this->once())
            ->method('withBatch')
            ->with($this->identicalTo($batch));

        $handler
            ->expects($this->once())
            ->method('middleware')
            ->willReturn([$middleware2]);

        $handler
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->willReturnCallback(function ($message) use (&$invoked): bool {
                $invoked[] = $message;
                return true;
            });

        $this->queue->through([$middleware1]);
        $this->queue->pushBatch($batch);

        $this->assertCount(2, $actual);
        $this->assertSame(['m1' => [$message1, $message2], 'm2' => [$message1, $message2]], $actual);
        $this->assertSame([$message1, $message2], $invoked);
    }
}
