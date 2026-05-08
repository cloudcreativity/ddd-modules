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
use CloudCreativity\Modules\Tests\Integration\Bus\NumbersDivided;
use CloudCreativity\Modules\Tests\Integration\Bus\NumbersSubtracted;
use PHPUnit\Framework\TestCase;

final class MathEventPublisherTest extends TestCase
{
    public function test(): void
    {
        $a = new NumbersAddedPublisher();
        $b = new NumbersSubtractedPublisher();
        $c = new TestDefaultPublisher();

        $container = new FakeContainer();
        $container->bind(NumbersAddedPublisher::class, fn () => $a);
        $container->bind(NumbersSubtractedPublisher::class, fn () => $b);
        $container->bind(TestDefaultPublisher::class, fn () => $c);
        $container->bind(LogOutboundEvent::class, fn () => new LogOutboundEvent($container->logger));

        $publisher = new MathEventPublisher($container);
        $publisher->publish($ev1 = new NumbersAdded(1, 2, 3));
        $publisher->publish($ev2 = new NumbersSubtracted(10, 6, 4));
        $publisher->publish($ev3 = new NumbersDivided(12, 3, 4));

        $this->assertSame([$ev1], $a->published);
        $this->assertSame([$ev2], $b->published);
        $this->assertSame([$ev3], $c->published);
        $this->assertCount(6, $container->logger);
    }
}
