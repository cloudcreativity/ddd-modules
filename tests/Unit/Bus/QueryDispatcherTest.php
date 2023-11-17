<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Bus;

use CloudCreativity\BalancedEvent\Common\Bus\QueryDispatcher;
use CloudCreativity\BalancedEvent\Common\Bus\QueryHandlerContainerInterface;
use CloudCreativity\BalancedEvent\Common\Bus\QueryHandlerInterface;
use CloudCreativity\BalancedEvent\Common\Bus\QueryInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ResultInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipelineBuilderFactory;
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
