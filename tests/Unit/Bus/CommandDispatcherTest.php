<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Bus;

use CloudCreativity\Modules\Bus\CommandDispatcher;
use CloudCreativity\Modules\Bus\CommandHandlerContainerInterface;
use CloudCreativity\Modules\Bus\CommandHandlerInterface;
use CloudCreativity\Modules\Bus\Queue\CommandEnqueuerInterface;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactory;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CommandDispatcherTest extends TestCase
{
    /**
     * @var CommandHandlerContainerInterface&MockObject
     */
    private CommandHandlerContainerInterface&MockObject $container;

    /**
     * @var PipeContainerInterface&MockObject
     */
    private PipeContainerInterface&MockObject $pipeContainer;

    /**
     * @var MockObject&CommandEnqueuerInterface
     */
    private CommandEnqueuerInterface&MockObject $enqueuer;

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

        $this->dispatcher = new CommandDispatcher(
            $this->container = $this->createMock(CommandHandlerContainerInterface::class),
            new PipelineBuilderFactory(
                $this->pipeContainer = $this->createMock(PipeContainerInterface::class),
            ),
            $this->enqueuer = $this->createMock(CommandEnqueuerInterface::class),
        );
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $this->willNotQueue();

        $command = $this->createMock(CommandInterface::class);

        $this->container
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

        $this->container
            ->method('get')
            ->with($command1::class)
            ->willReturn($handler = $this->createMock(CommandHandlerInterface::class));

        $this->pipeContainer
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
            $this->container,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Command dispatcher must have an enqueuer to queue commands.');

        $dispatcher->queue(new TestCommand());
    }

    /**
     * @return void
     */
    public function testItQueuesCommand(): void
    {
        $this->willNotDispatch();

        $this->enqueuer
            ->expects($this->once())
            ->method('queue')
            ->with($this->identicalTo($command = new TestCommand()));

        $this->dispatcher->queue($command);
    }

    /**
     * @return void
     */
    public function testItQueuesManyCommands(): void
    {
        $commands = [
            new TestCommand(),
            new TestCommand(),
            new TestCommand(),
        ];

        $sequence = [];

        $this->enqueuer
            ->expects($this->exactly(3))
            ->method('queue')
            ->with($this->callback(function ($cmd) use (&$sequence): bool {
                $sequence[] = $cmd;
                return true;
            }));

        $this->dispatcher->queue($commands);

        $this->assertSame($commands, $sequence);
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

    /**
     * @return void
     */
    private function willNotDispatch(): void
    {
        $this->container
            ->expects($this->never())
            ->method($this->anything());

        $this->pipeContainer
            ->expects($this->never())
            ->method($this->anything());
    }
}
