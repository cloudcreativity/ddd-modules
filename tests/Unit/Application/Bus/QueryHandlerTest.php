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

use CloudCreativity\Modules\Application\Bus\QueryHandler;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;
use PHPUnit\Framework\TestCase;

class QueryHandlerTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $query = new TestQuery();

        $innerHandler = $this->createMock(TestQueryHandler::class);
        $innerHandler
            ->expects($this->once())
            ->method('execute')
            ->with($this->identicalTo($query))
            ->willReturn($expected = $this->createMock(Result::class));

        $innerHandler
            ->expects($this->once())
            ->method('middleware')
            ->willReturn($middleware = ['Middleware1', 'Middleware2']);

        $handler = new QueryHandler($innerHandler);

        $this->assertSame($expected, $handler($query));
        $this->assertSame($middleware, $handler->middleware());
    }

    /**
     * @return void
     */
    public function testItDoesNotHaveExecuteMethod(): void
    {
        $handler = new QueryHandler(new \DateTime());
        $query = new TestQuery();
        $queryClass = $query::class;

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage(
            "Cannot dispatch \"{$queryClass}\" - handler \"DateTime\" does not have an execute method.",
        );

        $handler($query);
    }
}
