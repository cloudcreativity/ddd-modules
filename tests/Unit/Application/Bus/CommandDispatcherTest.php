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
use CloudCreativity\Modules\Contracts\Application\Bus\CommandHandler;
use CloudCreativity\Modules\Contracts\Application\Bus\CommandHandlerContainer;
use CloudCreativity\Modules\Contracts\Application\Messages\Command;
use CloudCreativity\Modules\Contracts\Application\Ports\Driven\Queue\Queue;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CommandDispatcherTest extends TestCase
{
    /**
     * @var CommandHandlerContainer&MockObject
     */
    private CommandHandlerContainer&MockObject $handlers;

    /**
     * @var PipeContainer&MockObject
     */
    private PipeContainer&MockObject $middleware;

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
            handlers: $this->handlers = $this->createMock(CommandHandlerContainer::class),
            middleware: $this->middleware = $this->createMock(PipeContainer::class),
            queue: fn () => $this->queue,
        );
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $this->willNotQueue();

        $command = $this->createMock(Command::class);

        $this->handlers
            ->expects($this->once())
            ->method('get')
            ->with($command::class)
            ->willReturn($handler = $this->createMock(CommandHandler::class));

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($command))
            ->willReturn($expected = $this->createMock(Result::class));

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
            ->willReturn($handler = $this->createMock(CommandHandler::class));

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
            ->willReturn($expected = $this->createMock(Result::class));

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
