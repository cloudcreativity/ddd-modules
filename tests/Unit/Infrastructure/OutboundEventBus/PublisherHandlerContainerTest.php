<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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
use RuntimeException;

class PublisherHandlerContainerTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $a = new TestPublisher();
        $b = $this->createMock(TestPublisher::class);

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
}
