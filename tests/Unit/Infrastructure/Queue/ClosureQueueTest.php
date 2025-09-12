<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\Queue;

use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Infrastructure\Queue\ClosureQueue;
use CloudCreativity\Modules\Tests\Unit\Application\Bus\TestCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClosureQueueTest extends TestCase
{
    private MockObject&PipeContainer $middleware;

    /**
     * @var array<Command>
     */
    private array $actual = [];

    private ClosureQueue $queue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queue = new ClosureQueue(
            function (Command $command): void {
                $this->actual[] = $command;
            },
            $this->middleware = $this->createMock(PipeContainer::class),
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->queue, $this->middleware, $this->actual);
    }

    public function test(): void
    {
        $command = $this->createMock(Command::class);

        $this->queue->push($command);

        $this->assertSame([$command], $this->actual);
    }

    public function testWithMiddleware(): void
    {
        $command1 = $this->createMock(Command::class);
        $command2 = $this->createMock(Command::class);
        $command3 = $this->createMock(Command::class);
        $command4 = $this->createMock(Command::class);

        $middleware1 = function ($command, \Closure $next) use ($command1, $command2) {
            $this->assertSame($command1, $command);
            return $next($command2);
        };

        $middleware2 = function ($command, \Closure $next) use ($command2, $command3) {
            $this->assertSame($command2, $command);
            return $next($command3);
        };

        $middleware3 = function ($command, \Closure $next) use ($command3, $command4) {
            $this->assertSame($command3, $command);
            return $next($command4);
        };

        $this->middleware
            ->expects($this->once())
            ->method('get')
            ->with('MySecondMiddleware')
            ->willReturn($middleware2);

        $this->queue->through([
            $middleware1,
            'MySecondMiddleware',
            $middleware3,
        ]);

        $this->queue->push($command1);

        $this->assertSame([$command4], $this->actual);
    }


    public function testWithAlternativeHandlers(): void
    {
        $expected = new TestCommand();
        $mock = $this->createMock(Command::class);
        $actual = null;

        $this->queue->bind($mock::class, function (Command $cmd): never {
            $this->fail('Not expecting this closure to be called.');
        });

        $this->queue->bind(
            TestCommand::class,
            function (TestCommand $cmd) use (&$actual) {
                $actual = $cmd;
            },
        );

        $this->queue->push($expected);

        $this->assertEmpty($this->actual);
        $this->assertSame($expected, $actual);
    }
}
