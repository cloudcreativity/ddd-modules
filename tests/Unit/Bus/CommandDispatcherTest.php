<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

namespace CloudCreativity\Modules\Tests\Unit\Bus;

use CloudCreativity\Modules\Bus\CommandDispatcher;
use CloudCreativity\Modules\Bus\CommandHandlerContainerInterface;
use CloudCreativity\Modules\Bus\CommandHandlerInterface;
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
    private CommandHandlerContainerInterface $container;

    /**
     * @var PipeContainerInterface&MockObject
     */
    private PipeContainerInterface $pipeContainer;

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
        );
    }

    /**
     * @return void
     */
    public function test(): void
    {
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
}
