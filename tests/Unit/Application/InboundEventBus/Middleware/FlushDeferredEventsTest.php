<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\InboundEventBus\Middleware;

use CloudCreativity\Modules\Application\InboundEventBus\Middleware\FlushDeferredEvents;
use CloudCreativity\Modules\Contracts\Application\DomainEventDispatching\DeferredDispatcher;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\IntegrationEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FlushDeferredEventsTest extends TestCase
{
    private DeferredDispatcher&MockObject $dispatcher;

    private FlushDeferredEvents $middleware;

    /**
     * @var array<string>
     */
    private array $sequence = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new FlushDeferredEvents(
            $this->dispatcher = $this->createMock(DeferredDispatcher::class),
        );
    }

    public function testItFlushesDeferredEvents(): void
    {
        $event = $this->createMock(IntegrationEvent::class);

        $this->dispatcher
            ->expects($this->once())
            ->method('flush')
            ->willReturnCallback(function () {
                $this->sequence[] = 'flush';
            });

        $this->dispatcher
            ->expects($this->never())
            ->method('forget');

        ($this->middleware)($event, function ($in) use ($event): void {
            $this->assertSame($event, $in);
            $this->sequence[] = 'next';
        });

        $this->assertSame(['next', 'flush'], $this->sequence);
    }


    public function testItForgetsDeferredEventsOnException(): void
    {
        $event = $this->createMock(IntegrationEvent::class);
        $expected = new \LogicException('Boom!');

        $this->dispatcher
            ->expects($this->once())
            ->method('forget')
            ->willReturnCallback(function () {
                $this->sequence[] = 'forget';
            });

        $this->dispatcher
            ->expects($this->never())
            ->method('flush');

        try {
            ($this->middleware)($event, function ($in) use ($event, $expected): never {
                $this->assertSame($event, $in);
                $this->sequence[] = 'next';
                throw $expected;
            });
            $this->fail('No exception thrown.');
        } catch (\LogicException $ex) {
            $this->assertSame($expected, $ex);
        }

        $this->assertSame(['next', 'forget'], $this->sequence);
    }
}
