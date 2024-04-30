<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\Bus;

use CloudCreativity\Modules\Application\Bus\CommandDispatcher;
use CloudCreativity\Modules\Application\Bus\CommandHandlerContainerInterface;
use CloudCreativity\Modules\Application\Bus\CommandHandlerInterface;
use CloudCreativity\Modules\Application\Messages\CommandInterface;
use CloudCreativity\Modules\Application\Ports\Driven\Queue\Queue;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CommandDispatcherTest extends TestCase
{
    /**
     * @var CommandHandlerContainerInterface&MockObject
     */
    private CommandHandlerContainerInterface&MockObject $handlers;

    /**
     * @var PipeContainerInterface&MockObject
     */
    private PipeContainerInterface&MockObject $middleware;

    /**
     * @var MockObject&Queue
     */
    private Queue&MockObject $queue;

    /**
     * @var CommandDispatcher
     */
    private CommandDispatcher $dispatcher;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->queue = $this->createMock(Queue::class);

        $this->dispatcher = new CommandDispatcher(
            handlers: $this->handlers = $this->createMock(CommandHandlerContainerInterface::class),
            middleware: $this->middleware = $this->createMock(PipeContainerInterface::class),
            queue: fn () => $this->queue,
        );
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $this->willNotQueue();

        $command = $this->createMock(CommandInterface::class);

        $this->handlers
            ->expects($this->once())
            ->method('get')
            ->with($command::class)
            ->willReturn($handler = $this->createMock(CommandHandlerInterface::class));

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($command))
            ->willReturn($expected = $this->createMock(ResultInterface::class));

        $actual = $this->dispatcher->dispatch($command);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testWithMiddleware(): void
    {
        $this->willNotQueue();

        $command1 = new TestCommand();
        $command2 = new TestCommand();
        $command3 = new TestCommand();
        $command4 = new TestCommand();

        $middleware1 = function (TestCommand $command, \Closure $next) use ($command1, $command2) {
            $this->assertSame($command1, $command);
            return $next($command2);
        };

        $middleware2 = function (TestCommand $command, \Closure $next) use ($command2, $command3) {
            $this->assertSame($command2, $command);
            return $next($command3);
        };

        $middleware3 = function (TestCommand $command, \Closure $next) use ($command3, $command4) {
            $this->assertSame($command3, $command);
            return $next($command4);
        };

        $this->handlers
            ->method('get')
            ->with($command1::class)
            ->willReturn($handler = $this->createMock(CommandHandlerInterface::class));

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

    /**
     * @return void
     */
    public function testItThrowsWhenItCannotQueueCommands(): void
    {
        $dispatcher = new CommandDispatcher(
            $this->handlers,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Commands cannot be queued because the command dispatcher has not been given a queue factory.',
        );

        $dispatcher->queue(new TestCommand());
    }

    /**
     * @return void
     */
    public function testItQueuesCommand(): void
    {
        $this->willNotDispatch();

        $this->queue
            ->expects($this->once())
            ->method('push')
            ->with($this->identicalTo($command = new TestCommand()));

        $this->dispatcher->queue($command);
    }

    /**
     * @return void
     */
    private function willNotQueue(): void
    {
        $this->queue
            ->expects($this->never())
            ->method($this->anything());
    }

    /**
     * @return void
     */
    private function willNotDispatch(): void
    {
        $this->handlers
            ->expects($this->never())
            ->method($this->anything());

        $this->middleware
            ->expects($this->never())
            ->method($this->anything());
    }
}
