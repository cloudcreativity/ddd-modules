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

use CloudCreativity\Modules\Contracts\Application\Messages\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Infrastructure\Queue\ComponentQueue;
use CloudCreativity\Modules\Infrastructure\Queue\EnqueuerContainerInterface;
use CloudCreativity\Modules\Infrastructure\Queue\EnqueuerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ComponentQueueTest extends TestCase
{
    /**
     * @var EnqueuerContainerInterface&MockObject
     */
    private EnqueuerContainerInterface&MockObject $enqueuers;

    /**
     * @var MockObject&PipeContainer
     */
    private PipeContainer&MockObject $middleware;

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
            middleware: $this->middleware = $this->createMock(PipeContainer::class),
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->queue, $this->enqueuers, $this->middleware);
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $command = $this->createMock(Command::class);

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
    public function testItQueuesThroughMiddleware(): void
    {
        $command1 = $this->createMock(Command::class);
        $command2 = $this->createMock(Command::class);
        $command3 = $this->createMock(Command::class);

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
