<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\InboundEventBus;

use CloudCreativity\Modules\Application\InboundEventBus\EventHandler;
use CloudCreativity\Modules\Application\InboundEventBus\EventHandlerContainer;
use CloudCreativity\Modules\Tests\Unit\Infrastructure\OutboundEventBus\TestOutboundEvent;
use PHPUnit\Framework\TestCase;

class EventHandlerContainerTest extends TestCase
{
    /**
     * @return void
     */
    public function testItHasHandlers(): void
    {
        $a = new TestEventHandler();
        $b = $this->createMock(TestEventHandler::class);

        $container = new EventHandlerContainer();
        $container->bind(TestInboundEvent::class, fn () => $a);
        $container->bind(TestOutboundEvent::class, fn () => $b);

        $this->assertEquals(new EventHandler($a), $container->get(TestInboundEvent::class));
        $this->assertEquals(new EventHandler($b), $container->get(TestOutboundEvent::class));
    }

    /**
     * @return void
     */
    public function testItHasDefaultHandler(): void
    {
        $a = new TestEventHandler();
        $b = $this->createMock(TestEventHandler::class);

        $container = new EventHandlerContainer(default: fn () => $b);
        $container->bind(TestInboundEvent::class, fn () => $a);

        $this->assertEquals(new EventHandler($a), $container->get(TestInboundEvent::class));
        $this->assertEquals(new EventHandler($b), $container->get(TestOutboundEvent::class));
    }


    /**
     * @return void
     */
    public function testItDoesNotHaveHandler(): void
    {
        $container = new EventHandlerContainer();
        $container->bind(TestInboundEvent::class, fn () => new TestEventHandler());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'No handler bound for integration event: ' . TestOutboundEvent::class,
        );

        $container->get(TestOutboundEvent::class);
    }
}
