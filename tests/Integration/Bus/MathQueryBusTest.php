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

use Closure;
use CloudCreativity\Modules\Bus\Middleware\LogMessageDispatch;
use CloudCreativity\Modules\Testing\FakeContainer;
use PHPUnit\Framework\TestCase;

final class MathQueryBusTest extends TestCase
{
    public function test(): void
    {
        $container = new FakeContainer();
        $container->bind(SubtractQueryHandler::class, fn () => new SubtractQueryHandler(3));
        $container->bind(DivideQueryHandler::class, fn () => new DivideQueryHandler());
        $container->bind(LogMessageDispatch::class, fn () => new LogMessageDispatch($container->logger));
        $container->bind('division-modifier', fn () => function (DivideQuery $query, Closure $next): mixed {
            $query = new DivideQuery($query->a * 10, $query->b);
            return $next($query);
        });

        $bus = new MathQueryBus($container);

        $add = $bus->dispatch(new SubtractQuery(1, 2));
        $multiply = $bus->dispatch(new DivideQuery(12, 3));

        $this->assertSame(1 - 2 - 3, $add->value());
        $this->assertEquals(120 / 3, $multiply->value());
        $this->assertCount(4, $container->logger);
    }
}
