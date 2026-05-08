<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\Bus;

use CloudCreativity\Modules\Bus\BusException;
use CloudCreativity\Modules\Bus\QueryHandler;
use CloudCreativity\Modules\Bus\QueryHandlerContainer;
use CloudCreativity\Modules\Contracts\Messaging\Query;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class QueryHandlerContainerTest extends TestCase
{
    public function testItResolvesClosureBindings(): void
    {
        $a = new class () extends TestQueryHandler {};
        $b = $this->createStub(TestQueryHandler::class);

        $query1 = new class () implements Query {};
        $query2 = new class () implements Query {};
        $query3 = new class () implements Query {};

        $container = new QueryHandlerContainer();
        $container->bind($query1::class, fn () => $a);
        $container->bind($query2::class, fn () => $b);

        $this->assertEquals(new QueryHandler($a), $container->get($query1::class));
        $this->assertEquals(new QueryHandler($b), $container->get($query2::class));

        $this->expectException(BusException::class);
        $this->expectExceptionMessage('No query handler bound for query class: ' . $query3::class);

        $container->get($query3::class);
    }

    public function testItResolvesViaPsrContainer(): void
    {
        $a = new class () extends TestQueryHandler {};
        $b = $this->createStub(TestQueryHandler::class);

        $query1 = new class () implements Query {};
        $query2 = new class () implements Query {};
        $query3 = new class () implements Query {};

        $psrContainer = $this->createMock(ContainerInterface::class);
        $psrContainer
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $id) => match ($id) {
                $a::class => $a,
                $b::class => $b,
                default => $this->fail('Unexpected container id: ' . $id),
            });

        $container = new QueryHandlerContainer($psrContainer);
        $container->bind($query1::class, $a::class);
        $container->bind($query2::class, $b::class);

        $this->assertEquals(new QueryHandler($a), $container->get($query1::class));
        $this->assertEquals(new QueryHandler($b), $container->get($query2::class));

        $this->expectException(BusException::class);
        $this->expectExceptionMessage('No query handler bound for query class: ' . $query3::class);

        $container->get($query3::class);
    }
}
