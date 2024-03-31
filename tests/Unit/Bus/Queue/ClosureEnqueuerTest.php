<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Bus\Queue;

use CloudCreativity\Modules\Bus\Queue\ClosureEnqueuer;
use CloudCreativity\Modules\Tests\Unit\Bus\TestCommand;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
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
     * @var CommandInterface|null
     */
    private ?CommandInterface $actual = null;

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
            function (CommandInterface $command) {
                $this->actual = $command;
            },
            $this->pipes = $this->createMock(PipeContainerInterface::class),
        );
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $command = $this->createMock(CommandInterface::class);

        $this->enqueuer->queue($command);

        $this->assertSame($command, $this->actual);
    }

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

        $this->enqueuer->queue($command1);

        $this->assertSame($command4, $this->actual);
    }
}
