<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\Bus;

use CloudCreativity\Modules\Application\ApplicationException;
use CloudCreativity\Modules\Application\Bus\QueryHandler;
use CloudCreativity\Modules\Application\Bus\QueryHandlerContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Query;
use PHPUnit\Framework\TestCase;

class QueryHandlerContainerTest extends TestCase
{
    public function test(): void
    {
        $a = new TestQueryHandler();
        $b = $this->createMock(TestQueryHandler::class);

        $query1 = new class () implements Query {};
        $query2 = new class () implements Query {};
        $query3 = new class () implements Query {};

        $container = new QueryHandlerContainer();
        $container->bind($query1::class, fn () => $a);
        $container->bind($query2::class, fn () => $b);

        $this->assertEquals(new QueryHandler($a), $container->get($query1::class));
        $this->assertEquals(new QueryHandler($b), $container->get($query2::class));

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('No query handler bound for query class: ' . $query3::class);

        $container->get($query3::class);
    }
}
