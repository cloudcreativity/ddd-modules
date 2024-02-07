<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Bus;

use CloudCreativity\Modules\Bus\QueryHandler;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;
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
            ->willReturn($expected = $this->createMock(ResultInterface::class));

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
