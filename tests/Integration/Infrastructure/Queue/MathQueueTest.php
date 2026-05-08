<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Integration\Infrastructure\Queue;

use CloudCreativity\Modules\Infrastructure\Queue\Middleware\LogPushedToQueue;
use CloudCreativity\Modules\Testing\FakeContainer;
use CloudCreativity\Modules\Tests\Integration\Bus\AddCommand;
use CloudCreativity\Modules\Tests\Integration\Bus\FloorCommand;
use CloudCreativity\Modules\Tests\Integration\Bus\MultiplyCommand;
use PHPUnit\Framework\TestCase;

final class MathQueueTest extends TestCase
{
    public function test(): void
    {
        $a = new AddCommandEnqueuer();
        $b = new MultiplyCommandEnqueuer();
        $c = new TestDefaultEnqueuer();

        $container = new FakeContainer();
        $container->bind(AddCommandEnqueuer::class, fn () => $a);
        $container->bind(MultiplyCommandEnqueuer::class, fn () => $b);
        $container->bind(TestDefaultEnqueuer::class, fn () => $c);
        $container->bind(LogPushedToQueue::class, fn () => new LogPushedToQueue($container->logger));

        $publisher = new MathQueue($container);
        $publisher->push($command1 = new AddCommand(1, 2));
        $publisher->push($command2 = new MultiplyCommand(10, 6));
        $publisher->push($command3 = new FloorCommand(99.9));

        $this->assertSame([$command1], $a->queued);
        $this->assertSame([$command2], $b->queued);
        $this->assertSame([$command3], $c->queued);
        $this->assertCount(6, $container->logger);
    }
}
