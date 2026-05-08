<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\Queue;

use CloudCreativity\Modules\Contracts\Messaging\Command;
use CloudCreativity\Modules\Infrastructure\Queue\Enqueuer;
use CloudCreativity\Modules\Infrastructure\Queue\EnqueuerContainer;
use CloudCreativity\Modules\Tests\Unit\Application\Bus\TestCommand;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class EnqueuerContainerTest extends TestCase
{
    public function testItUsesBindings(): void
    {
        $command1 = new class () implements Command {};
        $command2 = new class () implements Command {};

        $a = new class () extends TestEnqueuer {};
        $b = $this->createStub(TestEnqueuer::class);
        $default = $this->createStub(TestEnqueuer::class);

        $container = new EnqueuerContainer(fn () => $default);
        $container->bind($command1::class, fn () => $a);
        $container->bind($command2::class, fn () => $b);

        $this->assertEquals(new Enqueuer($a), $container->get($command1::class));
        $this->assertEquals(new Enqueuer($b), $container->get($command2::class));
        $this->assertEquals(new Enqueuer($default), $container->get(TestCommand::class));
    }

    public function testItUsesPsrContainer(): void
    {
        $command1 = new class () implements Command {};
        $command2 = new class () implements Command {};

        $a = new class () extends TestEnqueuer {};
        $b = $this->createStub(TestEnqueuer::class);
        $default = $this->createStub(TestEnqueuer::class);

        $psrContainer = $this->createMock(ContainerInterface::class);
        $psrContainer
            ->expects($this->exactly(3))
            ->method('get')
            ->willReturnCallback(fn (string $id) => match ($id) {
                $a::class => $a,
                $b::class => $b,
                $default::class => $default,
                default => $this->fail('Unexpected binding: ' . $id),
            });

        $container = new EnqueuerContainer(default: $default::class, container: $psrContainer);
        $container->bind($command1::class, $a::class);
        $container->bind($command2::class, $b::class);

        $this->assertEquals(new Enqueuer($a), $container->get($command1::class));
        $this->assertEquals(new Enqueuer($b), $container->get($command2::class));
        $this->assertEquals(new Enqueuer($default), $container->get(TestCommand::class));
    }
}
