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

use CloudCreativity\Modules\Infrastructure\Queue\ClosureEnqueuer;
use CloudCreativity\Modules\Infrastructure\Queue\QueueableInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClosureEnqueuerTest extends TestCase
{
    /**
     * @var MockObject&PipeContainerInterface
     */
    private PipeContainerInterface&MockObject $pipes;

    /**
     * @var QueueableInterface|null
     */
    private ?QueueableInterface $actual = null;

    /**
     * @var ClosureEnqueuer
     */
    private ClosureEnqueuer $enqueuer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->enqueuer = new ClosureEnqueuer(
            function (QueueableInterface $queueable) {
                $this->actual = $queueable;
            },
            $this->pipes = $this->createMock(PipeContainerInterface::class),
        );
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $command = $this->createMock(QueueableInterface::class);

        $this->enqueuer->queue($command);

        $this->assertSame($command, $this->actual);
    }

    /**
     * @return void
     */
    public function testWithMiddleware(): void
    {
        $queueable1 = $this->createMock(QueueableInterface::class);
        $queueable2 = $this->createMock(QueueableInterface::class);
        $queueable3 = $this->createMock(QueueableInterface::class);
        $queueable4 = $this->createMock(QueueableInterface::class);

        $middleware1 = function ($command, \Closure $next) use ($queueable1, $queueable2) {
            $this->assertSame($queueable1, $command);
            return $next($queueable2);
        };

        $middleware2 = function ($command, \Closure $next) use ($queueable2, $queueable3) {
            $this->assertSame($queueable2, $command);
            return $next($queueable3);
        };

        $middleware3 = function ($command, \Closure $next) use ($queueable3, $queueable4) {
            $this->assertSame($queueable3, $command);
            return $next($queueable4);
        };

        $this->pipes
            ->expects($this->once())
            ->method('get')
            ->with('MySecondMiddleware')
            ->willReturn($middleware2);

        $this->enqueuer->through([
            $middleware1,
            'MySecondMiddleware',
            $middleware3,
        ]);

        $this->enqueuer->queue($queueable1);

        $this->assertSame($queueable4, $this->actual);
    }


    /**
     * @return void
     */
    public function testWithAlternativeHandlers(): void
    {
        $expected = new TestQueueable();
        $mock = $this->createMock(QueueableInterface::class);
        $actual = null;

        $this->enqueuer->register($mock::class, function (QueueableInterface $cmd): never {
            $this->fail('Not expecting this closure to be called.');
        });

        $this->enqueuer->register(
            TestQueueable::class,
            function (TestQueueable $cmd) use (&$actual) {
                $actual = $cmd;
            },
        );

        $this->enqueuer->queue($expected);

        $this->assertNull($this->actual);
        $this->assertSame($expected, $actual);
    }
}
