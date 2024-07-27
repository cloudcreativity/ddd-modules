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
use CloudCreativity\Modules\Toolkit\Result\Result;
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
     * @var array<string>
     */
    private array $sequence = [];

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
    protected function tearDown(): void
    {
        unset($this->handlers, $this->middleware, $this->dispatcher, $this->sequence);
        parent::tearDown();
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
            ->willReturn($expected = Result::ok());

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
        $handler = $this->createMock(QueryHandler::class);

        $middleware1 = function (TestQuery $q, \Closure $next) use ($query1, $query2) {
            $this->assertSame($query1, $q);
            $this->sequence[] = 'before1';
            $result = $next($query2);
            $this->sequence[] = 'after1';
            return $result;
        };

        $middleware2 = function (TestQuery $q, \Closure $next) use ($query2, $query3) {
            $this->assertSame($query2, $q);
            $this->sequence[] = 'before2';
            $result = $next($query3);
            $this->sequence[] = 'after2';
            return $result;
        };

        $middleware3 = function (TestQuery $q, \Closure $next) use ($query3, $query4) {
            $this->assertSame($query3, $q);
            $this->sequence[] = 'before3';
            $result = $next($query4);
            $this->sequence[] = 'after3';
            return $result;
        };

        $this->handlers
            ->expects($this->once())
            ->method('get')
            ->with(TestQuery::class)
            ->willReturnCallback(function () use ($handler) {
                $this->assertSame(['before1'], $this->sequence);
                return $handler;
            });

        $this->middleware
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'MyFirstMiddleware' => $middleware1,
                'MySecondMiddleware' => $middleware2,
                default => $this->fail('Unexpected middleware: ' . $name),
            });

        $handler
            ->expects($this->once())
            ->method('middleware')
            ->willReturn(['MySecondMiddleware', $middleware3]);

        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($query4))
            ->willReturn($expected = Result::ok());

        $this->dispatcher->through(['MyFirstMiddleware']);
        $actual = $this->dispatcher->dispatch($query1);

        $this->assertSame($expected, $actual);
        $this->assertSame([
            'before1',
            'before2',
            'before3',
            'after3',
            'after2',
            'after1',
        ], $this->sequence);
    }
}
