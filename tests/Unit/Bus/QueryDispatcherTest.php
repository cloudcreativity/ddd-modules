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

use CloudCreativity\Modules\Bus\QueryDispatcher;
use CloudCreativity\Modules\Bus\QueryHandlerContainerInterface;
use CloudCreativity\Modules\Bus\QueryHandlerInterface;
use CloudCreativity\Modules\Toolkit\Messages\QueryInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactory;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QueryDispatcherTest extends TestCase
{
    /**
     * @var QueryHandlerContainerInterface&MockObject
     */
    private QueryHandlerContainerInterface $container;

    /**
     * @var PipeContainerInterface&MockObject
     */
    private PipeContainerInterface $pipeContainer;

    /**
     * @var QueryDispatcher
     */
    private QueryDispatcher $dispatcher;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = new QueryDispatcher(
            $this->container = $this->createMock(QueryHandlerContainerInterface::class),
            new PipelineBuilderFactory(
                $this->pipeContainer = $this->createMock(PipeContainerInterface::class),
            ),
        );
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $query = $this->createMock(QueryInterface::class);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with($query::class)
            ->willReturn($handler = $this->createMock(QueryHandlerInterface::class));

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($query))
            ->willReturn($expected = $this->createMock(ResultInterface::class));

        $actual = $this->dispatcher->dispatch($query);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testWithMiddleware(): void
    {
        $query1 = new TestQuery();
        $query2 = new TestQuery();
        $query3 = new TestQuery();
        $query4 = new TestQuery();

        $middleware1 = function (TestQuery $q, \Closure $next) use ($query1, $query2) {
            $this->assertSame($query1, $q);
            return $next($query2);
        };

        $middleware2 = function (TestQuery $q, \Closure $next) use ($query2, $query3) {
            $this->assertSame($query2, $q);
            return $next($query3);
        };

        $middleware3 = function (TestQuery $q, \Closure $next) use ($query3, $query4) {
            $this->assertSame($query3, $q);
            return $next($query4);
        };

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with(TestQuery::class)
            ->willReturn($handler = $this->createMock(QueryHandlerInterface::class));

        $this->pipeContainer
            ->expects($this->once())
            ->method('get')
            ->with('MySecondMiddleware')
            ->willReturn($middleware2);

        $handler
            ->expects($this->once())
            ->method('middleware')
            ->willReturn(['MySecondMiddleware', $middleware3]);

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($query4))
            ->willReturn($expected = $this->createMock(ResultInterface::class));

        $this->dispatcher->through([$middleware1]);
        $actual = $this->dispatcher->dispatch($query1);

        $this->assertSame($expected, $actual);
    }
}
