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

use CloudCreativity\Modules\Application\Bus\QueryDispatcher;
use CloudCreativity\Modules\Contracts\Application\Bus\QueryHandler;
use CloudCreativity\Modules\Contracts\Application\Bus\QueryHandlerContainer;
use CloudCreativity\Modules\Contracts\Application\Messages\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QueryDispatcherTest extends TestCase
{
    /**
     * @var QueryHandlerContainer&MockObject
     */
    private QueryHandlerContainer $handlers;

    /**
     * @var PipeContainer&MockObject
     */
    private PipeContainer $middleware;

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
            handlers: $this->handlers = $this->createMock(QueryHandlerContainer::class),
            middleware: $this->middleware = $this->createMock(PipeContainer::class),
        );
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $query = $this->createMock(Query::class);

        $this->handlers
            ->expects($this->once())
            ->method('get')
            ->with($query::class)
            ->willReturn($handler = $this->createMock(QueryHandler::class));

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($query))
            ->willReturn($expected = $this->createMock(Result::class));

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

        $this->handlers
            ->expects($this->once())
            ->method('get')
            ->with(TestQuery::class)
            ->willReturn($handler = $this->createMock(QueryHandler::class));

        $this->middleware
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
            ->willReturn($expected = $this->createMock(Result::class));

        $this->dispatcher->through([$middleware1]);
        $actual = $this->dispatcher->dispatch($query1);

        $this->assertSame($expected, $actual);
    }
}
