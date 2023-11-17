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

use CloudCreativity\BalancedEvent\Common\Bus\CommandHandler;
use CloudCreativity\BalancedEvent\Common\Bus\CommandHandlerContainer;
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
        $container->bind('CommandClassA', fn() => $a);
        $container->bind('CommandClassB', fn() => $b);

        $this->assertEquals(new CommandHandler($a), $container->get('CommandClassA'));
        $this->assertEquals(new CommandHandler($b), $container->get('CommandClassB'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No command handler bound for command class: CommandClassC');

        $container->get('CommandClassC');
    }
}