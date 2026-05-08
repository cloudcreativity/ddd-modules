<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\InboundEventBus;

use CloudCreativity\Modules\Bus\BusException;
use CloudCreativity\Modules\Bus\EventHandler;
use CloudCreativity\Modules\Bus\EventHandlerContainer;
use CloudCreativity\Modules\Tests\Unit\Infrastructure\OutboundEventBus\TestOutboundEvent;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class EventHandlerContainerTest extends TestCase
{
    public function testItHasHandlerBindings(): void
    {
        $a = new class () extends TestEventHandler {};
        $b = $this->createStub(TestEventHandler::class);

        $container = new EventHandlerContainer();
        $container->bind(TestInboundEvent::class, fn () => $a);
        $container->bind(TestOutboundEvent::class, fn () => $b);

        $this->assertEquals(new EventHandler($a), $container->get(TestInboundEvent::class));
        $this->assertEquals(new EventHandler($b), $container->get(TestOutboundEvent::class));
    }

    public function testItUsesPsrContainer(): void
    {
        $a = new class () extends TestEventHandler {};
        $b = $this->createStub(TestEventHandler::class);

        $psrContainer = $this->createMock(ContainerInterface::class);
        $psrContainer
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $class) => match ($class) {
                $a::class => $a,
                $b::class => $b,
                default => $this->fail('Unexpected class requested: ' . $class),
            });

        $container = new EventHandlerContainer(container: $psrContainer);
        $container->bind(TestInboundEvent::class, $a::class);
        $container->bind(TestOutboundEvent::class, $b::class);

        $this->assertEquals(new EventHandler($a), $container->get(TestInboundEvent::class));
        $this->assertEquals(new EventHandler($b), $container->get(TestOutboundEvent::class));
    }

    public function testItHasBoundDefaultHandler(): void
    {
        $a = new class () extends TestEventHandler {};
        $b = $this->createStub(TestEventHandler::class);

        $container = new EventHandlerContainer(default: fn () => $b);
        $container->bind(TestInboundEvent::class, fn () => $a);

        $this->assertEquals(new EventHandler($a), $container->get(TestInboundEvent::class));
        $this->assertEquals(new EventHandler($b), $container->get(TestOutboundEvent::class));
    }

    public function testItHasDefaultHandlerInPsrContainer(): void
    {
        $a = new class () extends TestEventHandler {};
        $b = $this->createStub(TestEventHandler::class);

        $psrContainer = $this->createMock(ContainerInterface::class);
        $psrContainer
            ->expects($this->once())
            ->method('get')
            ->with('default-handler')
            ->willReturn($b);

        $container = new EventHandlerContainer(default: 'default-handler', container: $psrContainer);
        $container->bind(TestInboundEvent::class, fn () => $a);

        $this->assertEquals(new EventHandler($a), $container->get(TestInboundEvent::class));
        $this->assertEquals(new EventHandler($b), $container->get(TestOutboundEvent::class));
    }


    public function testItDoesNotHaveHandler(): void
    {
        $container = new EventHandlerContainer();
        $container->bind(TestInboundEvent::class, fn () => new class () extends TestEventHandler {});

        $this->expectException(BusException::class);
        $this->expectExceptionMessage(
            'No handler bound for integration event: ' . TestOutboundEvent::class,
        );

        $container->get(TestOutboundEvent::class);
    }
}
