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

use CloudCreativity\Modules\Application\Bus\CommandQueuer;
use CloudCreativity\Modules\Contracts\Application\Ports\Driven\Queue;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CommandQueuerTest extends TestCase
{
    /**
     * @var MockObject&Queue
     */
    private Queue&MockObject $queue;

    /**
     * @var CommandQueuer
     */
    private CommandQueuer $queuer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->queuer = new CommandQueuer(
            $this->queue = $this->createMock(Queue::class),
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->queue, $this->queuer);
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $this->queue
            ->expects($this->once())
            ->method('push')
            ->with($this->identicalTo($command = new TestCommand()));

        $this->queuer->queue($command);
    }
}
