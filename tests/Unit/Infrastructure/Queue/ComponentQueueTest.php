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
use CloudCreativity\Modules\Contracts\Infrastructure\Queue\Enqueuer;
use CloudCreativity\Modules\Contracts\Infrastructure\Queue\EnqueuerContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Infrastructure\Queue\ComponentQueue;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ComponentQueueTest extends TestCase
{
    /**
     * @var EnqueuerContainer&MockObject
     */
    private EnqueuerContainer&MockObject $enqueuers;

    /**
     * @var MockObject&PipeContainer
     */
    private PipeContainer&MockObject $middleware;

    /**
     * @var ComponentQueue
     */
    private ComponentQueue $queue;

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

        $this->queue = new ComponentQueue(
            enqueuers: $this->enqueuers = $this->createMock(EnqueuerContainer::class),
            middleware: $this->middleware = $this->createMock(PipeContainer::class),
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->queue, $this->enqueuers, $this->middleware, $this->sequence);
        parent::tearDown();
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
            ->willReturn($enqueuer = $this->createMock(Enqueuer::class));

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
        $enqueuer = $this->createMock(Enqueuer::class);

        $middleware1 = function ($actual, \Closure $next) use ($command1, $command2): void {
            $this->assertSame($command1, $actual);
            $this->sequence[] = 'before1';
            $next($command2);
            $this->sequence[] = 'after1';
        };

        $middleware2 = function ($actual, \Closure $next) use ($command2, $command3): void {
            $this->assertSame($command2, $actual);
            $this->sequence[] = 'before2';
            $next($command3);
            $this->sequence[] = 'after2';
        };

        $this->enqueuers
            ->expects($this->once())
            ->method('get')
            ->with($command3::class)
            ->willReturnCallback(function () use ($enqueuer) {
                $this->assertSame(['before1', 'before2'], $this->sequence);
                return $enqueuer;
            });

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

        $this->assertSame(['before1', 'before2', 'after2', 'after1'], $this->sequence);
    }
}
