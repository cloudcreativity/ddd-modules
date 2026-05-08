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

final class MathCommandBusTest extends TestCase
{
    public function test(): void
    {
        $container = new FakeContainer();
        $container->bind(AddCommandHandler::class, fn () => new AddCommandHandler(3));
        $container->bind(MultiplyCommandHandler::class, fn () => new MultiplyCommandHandler());
        $container->bind(LogMessageDispatch::class, fn () => new LogMessageDispatch($container->logger));
        $container->bind(ExecuteInUnitOfWork::class, fn () => new ExecuteInUnitOfWork(
            new UnitOfWorkManager($container->unitOfWork),
        ));

        $bus = new MathCommandBus($container);

        $add = $bus->dispatch(new AddCommand(1, 2));
        $multiply = $bus->dispatch(new MultiplyCommand(10, 11));

        $this->assertSame(6, $add->value());
        $this->assertSame(110, $multiply->value());
        $this->assertCount(4, $container->logger);
        $this->assertSame(1, $container->unitOfWork->commits);
    }
}
