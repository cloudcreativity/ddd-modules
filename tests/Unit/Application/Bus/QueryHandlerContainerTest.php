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

use CloudCreativity\Modules\Application\Bus\QueryHandler;
use CloudCreativity\Modules\Application\Bus\QueryHandlerContainer;
use PHPUnit\Framework\TestCase;

class QueryHandlerContainerTest extends TestCase
{
    public function test(): void
    {
        $a = new TestQueryHandler();
        $b = $this->createMock(TestQueryHandler::class);

        $container = new QueryHandlerContainer();
        $container->bind('QueryClassA', fn () => $a);
        $container->bind('QueryClassB', fn () => $b);

        $this->assertEquals(new QueryHandler($a), $container->get('QueryClassA'));
        $this->assertEquals(new QueryHandler($b), $container->get('QueryClassB'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No query handler bound for query class: QueryClassC');

        $container->get('QueryClassC');
    }
}
