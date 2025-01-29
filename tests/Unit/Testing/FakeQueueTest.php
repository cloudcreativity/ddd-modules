<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Testing;

use CloudCreativity\Modules\Contracts\Application\Ports\Driven\Queue;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;
use CloudCreativity\Modules\Testing\FakeQueue;
use LogicException;
use PHPUnit\Framework\TestCase;

class FakeQueueTest extends TestCase
{
    public function testItQueuesCommands(): void
    {
        $queue = new FakeQueue();
        $queue->push($command1 = $this->createMock(Command::class));
        $queue->push($command2 = $this->createMock(Command::class));

        $this->assertInstanceOf(Queue::class, $queue);
        $this->assertCount(2, $queue);
        $this->assertSame([$command1, $command2], $queue->commands);
    }

    public function testItHasSoleCommand(): void
    {
        $queue = new FakeQueue();
        $queue->push($command = $this->createMock(Command::class));

        $this->assertSame($command, $queue->sole());
    }

    public function testItFailsWhenThereIsNoSoleCommand(): void
    {
        $this->expectExceptionMessage('Expected one command in the queue but there are 0 commands.');
        $this->expectException(LogicException::class);

        $queue = new FakeQueue();
        $queue->sole();
    }

    public function testItFailsWhenThereIsMoreThanOneSoleCommand(): void
    {
        $this->expectExceptionMessage('Expected one command in the queue but there are 2 commands.');
        $this->expectException(LogicException::class);

        $queue = new FakeQueue();
        $queue->push($this->createMock(Command::class));
        $queue->push($this->createMock(Command::class));
        $queue->sole();
    }

    public function testItCanBeExtended(): void
    {
        $queue = new class () extends FakeQueue {};

        $queue->push($command = $this->createMock(Command::class));
        $this->assertSame([$command], $queue->commands);
    }
}
