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
use CloudCreativity\BalancedEvent\Common\Bus\Results\ResultInterface;
use PHPUnit\Framework\TestCase;

class CommandHandlerTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $command = new TestCommand();

        $innerHandler = $this->createMock(TestCommandHandler::class);
        $innerHandler
            ->expects($this->once())
            ->method('execute')
            ->with($this->identicalTo($command))
            ->willReturn($expected = $this->createMock(ResultInterface::class));

        $innerHandler
            ->expects($this->once())
            ->method('middleware')
            ->willReturn($middleware = ['Middleware1', 'Middleware2']);

        $handler = new CommandHandler($innerHandler);

        $this->assertSame($expected, $handler($command));
        $this->assertSame($middleware, $handler->middleware());
    }

    /**
     * @return void
     */
    public function testItDoesNotHaveExecuteMethod(): void
    {
        $handler = new CommandHandler(new \DateTime());
        $command = new TestCommand();
        $commandClass = $command::class;

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage(
            "Cannot dispatch \"{$commandClass}\" - handler \"DateTime\" does not have an execute method.",
        );

        $handler($command);
    }
}