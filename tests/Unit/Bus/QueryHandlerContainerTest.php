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
use CloudCreativity\BalancedEvent\Common\Bus\QueryHandlerContainer;
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
        $container->bind('QueryClassA', fn() => $a);
        $container->bind('QueryClassB', fn() => $b);

        $this->assertEquals(new QueryHandler($a), $container->get('QueryClassA'));
        $this->assertEquals(new QueryHandler($b), $container->get('QueryClassB'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No query handler bound for query class: QueryClassC');

        $container->get('QueryClassC');
    }
}