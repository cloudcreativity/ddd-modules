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
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Toolkit\Result\Result;
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
     * @var CommandDispatcher
     */
    private CommandDispatcher $dispatcher;

    /**
     * @var array<string>
     */
    private array $sequence = [];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = new CommandDispatcher(
            handlers: $this->handlers = $this->createMock(CommandHandlerContainer::class),
            middleware: $this->middleware = $this->createMock(PipeContainer::class),
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->handlers, $this->middleware, $this->dispatcher, $this->sequence);
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function test(): void
    {
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
            ->willReturn($expected = Result::ok());

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
        $handler = $this->createMock(CommandHandler::class);

        $middleware1 = function (TestCommand $command, \Closure $next) use ($command1, $command2) {
            $this->assertSame($command1, $command);
            $this->sequence[] = 'before1';
            $result = $next($command2);
            $this->sequence[] = 'after1';
            return $result;
        };

        $middleware2 = function (TestCommand $command, \Closure $next) use ($command2, $command3) {
            $this->assertSame($command2, $command);
            $this->sequence[] = 'before2';
            $result = $next($command3);
            $this->sequence[] = 'after2';
            return $result;
        };

        $middleware3 = function (TestCommand $command, \Closure $next) use ($command3, $command4) {
            $this->assertSame($command3, $command);
            $this->sequence[] = 'before3';
            $result = $next($command4);
            $this->sequence[] = 'after3';
            return $result;
        };

        $this->handlers
            ->method('get')
            ->with($command1::class)
            ->willReturnCallback(function () use ($handler) {
                $this->assertSame(['before1'], $this->sequence);
                return $handler;
            });

        $this->middleware
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'MyFirstMiddleware' => $middleware1,
                'MySecondMiddleware' => $middleware2,
                default => $this->fail('Unexpected middleware: ' . $name),
            });

        $handler
            ->expects($this->once())
            ->method('middleware')
            ->willReturn(['MySecondMiddleware', $middleware3]);

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($command4))
            ->willReturn($expected = Result::ok());

        $this->dispatcher->through(['MyFirstMiddleware']);
        $actual = $this->dispatcher->dispatch($command1);

        $this->assertSame($expected, $actual);
        $this->assertSame([
            'before1',
            'before2',
            'before3',
            'after3',
            'after2',
            'after1',
        ], $this->sequence);
    }
}
