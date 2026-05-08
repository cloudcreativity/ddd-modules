<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Bus;

use CloudCreativity\Modules\Bus\PsrPipeContainer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class PsrPipeContainerTest extends TestCase
{
    public function testItResolvesBoundPipes(): void
    {
        $a = fn () => 1;
        $b = fn () => 2;

        $container = new PsrPipeContainer();
        $container->bind('PipeA', fn () => $a);
        $container->bind('PipeB', fn () => $b);

        $this->assertSame($a, $container->get('PipeA'));
        $this->assertSame($b, $container->get('PipeB'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unrecognised pipe name: PipeC');

        $container->get('PipeC');
    }

    public function testItFallsBackToPsrContainer(): void
    {
        $psrContainer = $this->createMock(ContainerInterface::class);

        $a = fn () => 1;
        $b = fn () => 2;
        $c = fn () => 3;

        $container = new PsrPipeContainer($psrContainer);
        $container->bind('PipeA', fn () => $a);
        $container->bind('PipeB', fn () => $b);

        $psrContainer
            ->expects($this->once())
            ->method('get')
            ->with('PipeC')
            ->willReturn($c);

        $this->assertSame($a, $container->get('PipeA'));
        $this->assertSame($b, $container->get('PipeB'));
        $this->assertSame($c, $container->get('PipeC'));
    }
}
