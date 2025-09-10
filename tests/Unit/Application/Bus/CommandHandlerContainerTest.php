<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\Bus;

use CloudCreativity\Modules\Application\ApplicationException;
use CloudCreativity\Modules\Application\Bus\CommandHandler;
use CloudCreativity\Modules\Application\Bus\CommandHandlerContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;
use PHPUnit\Framework\TestCase;

class CommandHandlerContainerTest extends TestCase
{
    public function test(): void
    {
        $a = new TestCommandHandler();
        $b = $this->createMock(TestCommandHandler::class);

        $command1 = new class () implements Command {};
        $command2 = new class () implements Command {};
        $command3 = new class () implements Command {};

        $container = new CommandHandlerContainer();
        $container->bind($command1::class, fn () => $a);
        $container->bind($command2::class, fn () => $b);

        $this->assertEquals(new CommandHandler($a), $container->get($command1::class));
        $this->assertEquals(new CommandHandler($b), $container->get($command2::class));

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('No command handler bound for command class: ' . $command3::class);

        $container->get($command3::class);
    }
}
