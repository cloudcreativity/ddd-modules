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

use CloudCreativity\Modules\Infrastructure\Queue\ComponentQueue;
use CloudCreativity\Modules\Infrastructure\Queue\EnqueuerContainerInterface;
use CloudCreativity\Modules\Infrastructure\Queue\EnqueuerInterface;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ComponentQueueTest extends TestCase
{
    /**
     * @var EnqueuerContainerInterface&MockObject
     */
    private EnqueuerContainerInterface&MockObject $enqueuers;

    /**
     * @var MockObject&PipeContainerInterface
     */
    private PipeContainerInterface&MockObject $middleware;

    /**
     * @var ComponentQueue
     */
    private ComponentQueue $queue;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->queue = new ComponentQueue(
            enqueuers: $this->enqueuers = $this->createMock(EnqueuerContainerInterface::class),
            middleware: $this->middleware = $this->createMock(PipeContainerInterface::class),
        );
    }

    /**
     * @return void
     */
    public function testItQueuesCommand(): void
    {
        $command = $this->createMock(CommandInterface::class);

        $this->enqueuers
            ->expects($this->once())
            ->method('get')
            ->with($command::class)
            ->willReturn($enqueuer = $this->createMock(EnqueuerInterface::class));

        $enqueuer
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($command));

        $this->queue->push($command);
    }

    /**
     * @return void
     */
    public function testItQueuesCommands(): void
    {
        $command1 = $this->createMock(CommandInterface::class);
        $command2 = $this->createMock(CommandInterface::class);
        $command3 = $this->createMock(CommandInterface::class);
        $actual = [];

        $this->enqueuers
            ->expects($this->exactly(3))
            ->method('get')
            ->willReturn($enqueuer = $this->createMock(EnqueuerInterface::class));

        $enqueuer
            ->expects($this->exactly(3))
            ->method('__invoke')
            ->with($this->callback(function ($cmd) use (&$actual): bool {
                $actual[] = $cmd;
                return true;
            }));

        $this->queue->push($expected = [$command1, $command2, $command3]);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItQueuesThroughMiddleware(): void
    {
        $command1 = $this->createMock(CommandInterface::class);
        $command2 = $this->createMock(CommandInterface::class);
        $command3 = $this->createMock(CommandInterface::class);

        $middleware1 = function ($actual, \Closure $next) use ($command1, $command2) {
            $this->assertSame($command1, $actual);
            return $next($command2);
        };

        $middleware2 = function ($actual, \Closure $next) use ($command2, $command3) {
            $this->assertSame($command2, $actual);
            return $next($command3);
        };

        $this->enqueuers
            ->expects($this->once())
            ->method('get')
            ->with($command1::class)
            ->willReturn($enqueuer = $this->createMock(EnqueuerInterface::class));

        $enqueuer
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($command3));

        $this->middleware
            ->expects($this->once())
            ->method('get')
            ->with('MySecondMiddleware')
            ->willReturn($middleware2);

        $this->queue->through([$middleware1, 'MySecondMiddleware']);
        $this->queue->push($command1);
    }
}
