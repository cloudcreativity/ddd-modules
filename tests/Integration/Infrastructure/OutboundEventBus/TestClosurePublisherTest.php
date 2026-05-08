<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Integration\Infrastructure\OutboundEventBus;

use CloudCreativity\Modules\Infrastructure\OutboundEventBus\Middleware\LogOutboundEvent;
use CloudCreativity\Modules\Testing\FakeContainer;
use CloudCreativity\Modules\Tests\Integration\Bus\NumbersAdded;
use PHPUnit\Framework\TestCase;

final class TestClosurePublisherTest extends TestCase
{
    public function test(): void
    {
        $published = [];

        $container = new FakeContainer();
        $container->bind(LogOutboundEvent::class, fn () => new LogOutboundEvent($container->logger));

        $publisher = new TestClosurePublisher(
            fn: function ($event) use (&$published) {
                $published[] = $event;
            },
            middleware: $container,
        );

        $event = new NumbersAdded(1, 2, 3);
        $publisher->publish($event);

        $this->assertSame([$event], $published);
        $this->assertCount(2, $container->logger);
    }
}
