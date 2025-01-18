<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Testing;

use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEventDispatcher;
use CloudCreativity\Modules\Testing\FakeDomainEventDispatcher;
use LogicException;
use PHPUnit\Framework\TestCase;

class FakeDomainEventDispatcherTest extends TestCase
{
    public function testItPublishesEvents(): void
    {
        $dispatcher = new FakeDomainEventDispatcher();

        $event1 = $this->createMock(DomainEvent::class);
        $event2 = $this->createMock(DomainEvent::class);

        $dispatcher->dispatch($event1);
        $dispatcher->dispatch($event2);

        $this->assertInstanceOf(DomainEventDispatcher::class, $dispatcher);
        $this->assertCount(2, $dispatcher->events);
        $this->assertSame($event1, $dispatcher->events[0]);
        $this->assertSame($event2, $dispatcher->events[1]);
    }

    public function testItReturnsSoleEvent(): void
    {
        $dispatcher = new FakeDomainEventDispatcher();
        $event = $this->createMock(DomainEvent::class);

        $dispatcher->dispatch($event);

        $this->assertSame($event, $dispatcher->sole());
    }

    public function testItThrowsExceptionIfNoEvents(): void
    {
        $dispatcher = new FakeDomainEventDispatcher();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Expected one event to be dispatched but there are 0 events.');

        $dispatcher->sole();
    }

    public function testItThrowsExceptionIfMultipleEvents(): void
    {
        $dispatcher = new FakeDomainEventDispatcher();
        $event1 = $this->createMock(DomainEvent::class);
        $event2 = $this->createMock(DomainEvent::class);

        $dispatcher->dispatch($event1);
        $dispatcher->dispatch($event2);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Expected one event to be dispatched but there are 2 events.');

        $dispatcher->sole();
    }

    public function testItCanBeExtended(): void
    {
        $dispatcher = new class () extends FakeDomainEventDispatcher {};
        $dispatcher->dispatch($this->createMock(DomainEvent::class));
        $this->assertCount(1, $dispatcher->events);
    }
}
