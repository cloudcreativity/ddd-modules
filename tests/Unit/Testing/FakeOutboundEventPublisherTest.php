<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Testing;

use CloudCreativity\Modules\Contracts\Application\Ports\OutboundEventPublisher;
use CloudCreativity\Modules\Contracts\Messaging\IntegrationEvent;
use CloudCreativity\Modules\Testing\FakeOutboundEventPublisher;
use LogicException;
use PHPUnit\Framework\TestCase;

final class FakeOutboundEventPublisherTest extends TestCase
{
    public function testItPublishesEvents(): void
    {
        $publisher = new class () extends FakeOutboundEventPublisher {};

        $event1 = $this->createMock(IntegrationEvent::class);
        $event2 = $this->createMock(IntegrationEvent::class);

        $publisher->publish($event1);
        $publisher->publish($event2);

        $this->assertInstanceOf(OutboundEventPublisher::class, $publisher);
        $this->assertCount(2, $publisher);
        $this->assertSame([$event1, $event2], $publisher->events);
        $this->assertSame([$event1, $event2], iterator_to_array($publisher));
        $this->assertSame($event1, $publisher[0]);
        $this->assertSame($event2, $publisher[1]);
    }

    public function testItReturnsSoleEvent(): void
    {
        $publisher = new class () extends FakeOutboundEventPublisher {};
        $event = $this->createMock(IntegrationEvent::class);

        $publisher->publish($event);

        $this->assertSame($event, $publisher->sole());
    }

    public function testItThrowsExceptionIfNoEvents(): void
    {
        $publisher = new class () extends FakeOutboundEventPublisher {};

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Expected one event to be published but there are 0 events.');

        $publisher->sole();
    }

    public function testItThrowsExceptionIfMultipleEvents(): void
    {
        $publisher = new class () extends FakeOutboundEventPublisher {};
        $event1 = $this->createMock(IntegrationEvent::class);
        $event2 = $this->createMock(IntegrationEvent::class);

        $publisher->publish($event1);
        $publisher->publish($event2);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Expected one event to be published but there are 2 events.');

        $publisher->sole();
    }
}
