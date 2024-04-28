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

use CloudCreativity\Modules\Application\Messages\CommandInterface;
use CloudCreativity\Modules\Infrastructure\Queue\ClosureQueue;
use CloudCreativity\Modules\Tests\Unit\Application\Bus\TestCommand;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClosureQueueTest extends TestCase
{
    /**
     * @var MockObject&PipeContainerInterface
     */
    private PipeContainerInterface&MockObject $middleware;

    /**
     * @var array<CommandInterface>
     */
    private array $actual = [];

    /**
     * @var ClosureQueue
     */
    private ClosureQueue $queue;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->queue = new ClosureQueue(
            function (CommandInterface $command): void {
                $this->actual[] = $command;
            },
            $this->middleware = $this->createMock(PipeContainerInterface::class),
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->queue, $this->middleware, $this->actual);
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $command = $this->createMock(CommandInterface::class);

        $this->queue->push($command);

        $this->assertSame([$command], $this->actual);
    }

    /**
     * @return void
     */
    public function testWithMiddleware(): void
    {
        $command1 = $this->createMock(CommandInterface::class);
        $command2 = $this->createMock(CommandInterface::class);
        $command3 = $this->createMock(CommandInterface::class);
        $command4 = $this->createMock(CommandInterface::class);

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


    /**
     * @return void
     */
    public function testWithAlternativeHandlers(): void
    {
        $expected = new TestCommand();
        $mock = $this->createMock(CommandInterface::class);
        $actual = null;

        $this->queue->bind($mock::class, function (CommandInterface $cmd): never {
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
