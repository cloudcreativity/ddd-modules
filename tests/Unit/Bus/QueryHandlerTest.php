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

use CloudCreativity\BalancedEvent\Common\Bus\QueryHandler;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ResultInterface;
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