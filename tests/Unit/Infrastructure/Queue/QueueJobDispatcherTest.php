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

use CloudCreativity\Modules\Infrastructure\Queue\QueueJobDispatcher;
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobHandlerContainerInterface;
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobHandlerInterface;
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QueueJobDispatcherTest extends TestCase
{
    /**
     * @var QueueJobHandlerContainerInterface&MockObject
     */
    private QueueJobHandlerContainerInterface&MockObject $handlers;

    /**
     * @var PipeContainerInterface&MockObject
     */
    private PipeContainerInterface&MockObject $middleware;

    /**
     * @var QueueJobDispatcher
     */
    private QueueJobDispatcher $dispatcher;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = new QueueJobDispatcher(
            handlers: $this->handlers = $this->createMock(QueueJobHandlerContainerInterface::class),
            middleware: $this->middleware = $this->createMock(PipeContainerInterface::class),
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->dispatcher, $this->handlers, $this->middleware);
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $job = $this->createMock(QueueJobInterface::class);

        $this->handlers
            ->expects($this->once())
            ->method('get')
            ->with($job::class)
            ->willReturn($handler = $this->createMock(QueueJobHandlerInterface::class));

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($job))
            ->willReturn($expected = $this->createMock(ResultInterface::class));

        $actual = $this->dispatcher->dispatch($job);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testWithMiddleware(): void
    {
        $command1 = new TestQueueJob();
        $command2 = new TestQueueJob();
        $command3 = new TestQueueJob();
        $command4 = new TestQueueJob();

        $middleware1 = function (TestQueueJob $job, \Closure $next) use ($command1, $command2) {
            $this->assertSame($command1, $job);
            return $next($command2);
        };

        $middleware2 = function (TestQueueJob $job, \Closure $next) use ($command2, $command3) {
            $this->assertSame($command2, $job);
            return $next($command3);
        };

        $middleware3 = function (TestQueueJob $job, \Closure $next) use ($command3, $command4) {
            $this->assertSame($command3, $job);
            return $next($command4);
        };

        $this->handlers
            ->method('get')
            ->with($command1::class)
            ->willReturn($handler = $this->createMock(QueueJobHandlerInterface::class));

        $this->middleware
            ->expects($this->once())
            ->method('get')
            ->with('MySecondMiddleware')
            ->willReturn($middleware2);

        $handler
            ->expects($this->once())
            ->method('middleware')
            ->willReturn(['MySecondMiddleware', $middleware3]);

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($command4))
            ->willReturn($expected = $this->createMock(ResultInterface::class));

        $this->dispatcher->through([$middleware1]);
        $actual = $this->dispatcher->dispatch($command1);

        $this->assertSame($expected, $actual);
    }
}
