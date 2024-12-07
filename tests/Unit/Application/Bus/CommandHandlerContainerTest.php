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

use CloudCreativity\Modules\Application\Bus\CommandHandler;
use CloudCreativity\Modules\Application\Bus\CommandHandlerContainer;
use PHPUnit\Framework\TestCase;

class CommandHandlerContainerTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $a = new TestCommandHandler();
        $b = $this->createMock(TestCommandHandler::class);

        $container = new CommandHandlerContainer();
        $container->bind('CommandClassA', fn () => $a);
        $container->bind('CommandClassB', fn () => $b);

        $this->assertEquals(new CommandHandler($a), $container->get('CommandClassA'));
        $this->assertEquals(new CommandHandler($b), $container->get('CommandClassB'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No command handler bound for command class: CommandClassC');

        $container->get('CommandClassC');
    }
}
