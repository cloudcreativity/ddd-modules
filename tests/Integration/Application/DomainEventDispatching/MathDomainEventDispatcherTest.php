<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Integration\Application\DomainEventDispatching;

use CloudCreativity\Modules\Application\DomainEventDispatching\Middleware\LogDomainEventDispatch;
use CloudCreativity\Modules\Application\UnitOfWork\UnitOfWorkManager;
use CloudCreativity\Modules\Testing\FakeContainer;
use PHPUnit\Framework\TestCase;

final class MathDomainEventDispatcherTest extends TestCase
{
    public function test(): void
    {
        $container = new FakeContainer();
        $listener = new TestDomainListener();
        $unitOfWorkManager = new UnitOfWorkManager($container->unitOfWork);

        $container->bind(TestDomainListener::class, fn () => $listener);
        $container->bind(LogDomainEventDispatch::class, fn () => new LogDomainEventDispatch($container->logger));

        $dispatcher = new MathDomainEventDispatcher(
            unitOfWorkManager: $unitOfWorkManager,
            listeners: $container,
        );

        $add = new NumbersAdded(1, 2, 3);
        $subtract = new NumbersSubtracted(5, 4, 1);

        $unitOfWorkManager->execute(function () use ($add, $subtract, $dispatcher) {
            $dispatcher->dispatch($add);
            $dispatcher->dispatch($subtract);
        });

        $this->assertSame([
            $add,
            $subtract,
            $subtract,
        ], $listener->events);
        $this->assertCount(4, $container->logger);
    }
}
