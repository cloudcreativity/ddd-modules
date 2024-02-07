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
use CloudCreativity\Modules\Bus\QueryHandlerContainer;
use PHPUnit\Framework\TestCase;

class QueryHandlerContainerTest extends TestCase
{
    /**
     * @return void
     */
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
