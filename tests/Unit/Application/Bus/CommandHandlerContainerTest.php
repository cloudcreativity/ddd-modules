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
use CloudCreativity\Modules\Bus\CommandHandler;
use CloudCreativity\Modules\Bus\CommandHandlerContainer;
use CloudCreativity\Modules\Contracts\Messaging\Command;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class CommandHandlerContainerTest extends TestCase
{
    public function testItResolvesUsingClosureBindings(): void
    {
        $a = new class () extends TestCommandHandler {};
        $b = $this->createStub(TestCommandHandler::class);

        $command1 = new class () implements Command {};
        $command2 = new class () implements Command {};
        $command3 = new class () implements Command {};

        $container = new CommandHandlerContainer();
        $container->bind($command1::class, fn () => $a);
        $container->bind($command2::class, fn () => $b);

        $this->assertEquals(new CommandHandler($a), $container->get($command1::class));
        $this->assertEquals(new CommandHandler($b), $container->get($command2::class));

        $this->expectException(BusException::class);
        $this->expectExceptionMessage('No command handler bound for command class: ' . $command3::class);

        $container->get($command3::class);
    }

    public function testItResolvesViaPsrContainer(): void
    {
        $a = new class () extends TestCommandHandler {};
        $b = $this->createStub(TestCommandHandler::class);

        $command1 = new class () implements Command {};
        $command2 = new class () implements Command {};
        $command3 = new class () implements Command {};

        $psrContainer = $this->createMock(ContainerInterface::class);
        $psrContainer
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $id) => match ($id) {
                $a::class => $a,
                $b::class => $b,
                default => $this->fail('Unexpected container id: ' . $id),
            });

        $container = new CommandHandlerContainer($psrContainer);
        $container->bind($command1::class, $a::class);
        $container->bind($command2::class, $b::class);

        $this->assertEquals(new CommandHandler($a), $container->get($command1::class));
        $this->assertEquals(new CommandHandler($b), $container->get($command2::class));

        $this->expectException(BusException::class);
        $this->expectExceptionMessage('No command handler bound for command class: ' . $command3::class);

        $container->get($command3::class);
    }
}
