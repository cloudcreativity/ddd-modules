<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Integration\Bus;

use CloudCreativity\Modules\Application\Bus\Middleware\ExecuteInUnitOfWork;
use CloudCreativity\Modules\Application\UnitOfWork\UnitOfWorkManager;
use CloudCreativity\Modules\Bus\Middleware\LogMessageDispatch;
use CloudCreativity\Modules\Testing\FakeContainer;
use PHPUnit\Framework\TestCase;

final class MathEventBusTest extends TestCase
{
    public function test(): void
    {
        $a = new NumbersAddedHandler();
        $b = new NumbersSubtractedHandler();
        $c = new DefaultEventHandler();

        $container = new FakeContainer();
        $container->bind(NumbersAddedHandler::class, fn () => $a);
        $container->bind(NumbersSubtractedHandler::class, fn () => $b);
        $container->bind(DefaultEventHandler::class, fn () => $c);
        $container->bind(LogMessageDispatch::class, fn () => new LogMessageDispatch($container->logger));
        $container->bind(ExecuteInUnitOfWork::class, fn () => new ExecuteInUnitOfWork(
            new UnitOfWorkManager($container->unitOfWork),
        ));

        $bus = new MathEventBus($container);

        $bus->dispatch($ev1 = new NumbersAdded(1, 2, 3));
        $bus->dispatch($ev2 = new NumbersSubtracted(10, 6, 4));
        $bus->dispatch($ev3 = new NumbersDivided(12, 3, 4));

        $this->assertSame([$ev1], $a->handled);
        $this->assertSame([$ev2], $b->handled);
        $this->assertSame([$ev3], $c->handled);
        $this->assertCount(6, $container->logger);
        $this->assertSame(1, $container->unitOfWork->commits);
    }
}
