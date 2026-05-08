<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\OutboundEventBus;

use CloudCreativity\Modules\Infrastructure\OutboundEventBus\PublisherHandler;
use CloudCreativity\Modules\Infrastructure\OutboundEventBus\PublisherHandlerContainer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

final class PublisherHandlerContainerTest extends TestCase
{
    public function testItPublishesViaBindingsWithoutDefaultHandler(): void
    {
        $a = new class () extends TestPublisher {};
        $b = $this->createStub(TestPublisher::class);

        $event1 = new class () extends TestOutboundEvent {};
        $event2 = new class () extends TestOutboundEvent {};
        $event3 = new class () extends TestOutboundEvent {};

        $container = new PublisherHandlerContainer();
        $container->bind($event1::class, fn () => $a);
        $container->bind($event2::class, fn () => $b);

        $this->assertEquals(new PublisherHandler($a), $container->get($event1::class));
        $this->assertEquals(new PublisherHandler($b), $container->get($event2::class));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No handler bound for integration event: ' . $event3::class);

        $container->get($event3::class);
    }

    public function testItPublishesViaBindingsWithDefaultHandler(): void
    {
        $a = new class () extends TestPublisher {};
        $b = $this->createStub(TestPublisher::class);

        $event1 = new class () extends TestOutboundEvent {};
        $event2 = new class () extends TestOutboundEvent {};
        $event3 = new class () extends TestOutboundEvent {};

        $container = new PublisherHandlerContainer(default: fn () => $b);
        $container->bind($event1::class, fn () => $a);

        $this->assertEquals(new PublisherHandler($a), $container->get($event1::class));
        $this->assertEquals($default = new PublisherHandler($b), $container->get($event2::class));
        $this->assertEquals($default, $container->get($event3::class));
    }

    public function testItPublishesViaPsrContainerWithoutDefaultHandler(): void
    {
        $a = new class () extends TestPublisher {};
        $b = $this->createStub(TestPublisher::class);

        $event1 = new class () extends TestOutboundEvent {};
        $event2 = new class () extends TestOutboundEvent {};
        $event3 = new class () extends TestOutboundEvent {};

        $psrContainer = $this->createMock(ContainerInterface::class);
        $psrContainer
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $id) => match ($id) {
                $a::class => $a,
                $b::class => $b,
                default => $this->fail('Unexpected container id: ' . $id),
            });

        $container = new PublisherHandlerContainer(container: $psrContainer);
        $container->bind($event1::class, $a::class);
        $container->bind($event2::class, $b::class);

        $this->assertEquals(new PublisherHandler($a), $container->get($event1::class));
        $this->assertEquals(new PublisherHandler($b), $container->get($event2::class));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No handler bound for integration event: ' . $event3::class);

        $container->get($event3::class);
    }

    public function testItPublishesViaPsrContainerWithDefaultHandler(): void
    {
        $a = new class () extends TestPublisher {};
        $b = $this->createStub(TestPublisher::class);

        $event1 = new class () extends TestOutboundEvent {};
        $event2 = new class () extends TestOutboundEvent {};
        $event3 = new class () extends TestOutboundEvent {};

        $psrContainer = $this->createMock(ContainerInterface::class);
        $psrContainer
            ->expects($this->exactly(3))
            ->method('get')
            ->willReturnCallback(fn (string $id) => match ($id) {
                $a::class => $a,
                $b::class => $b,
                default => $this->fail('Unexpected container id: ' . $id),
            });

        $container = new PublisherHandlerContainer(default: $b::class, container: $psrContainer);
        $container->bind($event1::class, $a::class);

        $this->assertEquals(new PublisherHandler($a), $container->get($event1::class));
        $this->assertEquals($default = new PublisherHandler($b), $container->get($event2::class));
        $this->assertEquals($default, $container->get($event3::class));
    }
}
