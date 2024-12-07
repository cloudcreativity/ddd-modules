<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\InboundEventBus;

use CloudCreativity\Modules\Application\InboundEventBus\SwallowInboundEvent;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class SwallowInboundEventTest extends TestCase
{
    /**
     * @return void
     */
    public function testItDoesNothing(): void
    {
        $handler = new SwallowInboundEvent();
        $handler->handle(new TestInboundEvent());
        /** @phpstan-ignore-next-line */
        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testItLogsThatItDoesNothing(): void
    {
        $event = new TestInboundEvent();
        $name = ModuleBasename::from($event);
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                LogLevel::DEBUG,
                "Swallowing inbound integration event {$name}.",
                [],
            );

        $handler = new SwallowInboundEvent($logger);
        $handler->handle($event);
    }

    /**
     * @return void
     */
    public function testItLogsThatItDoesNothingWithSpecifiedLogLevel(): void
    {
        $event = new TestInboundEvent();
        $name = ModuleBasename::from($event);
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                LogLevel::NOTICE,
                "Swallowing inbound integration event {$name}.",
                [],
            );

        $handler = new SwallowInboundEvent(logger: $logger, level: LogLevel::NOTICE);
        $handler->handle($event);
    }
}
