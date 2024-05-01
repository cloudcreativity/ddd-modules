<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\InboundEventBus\Middleware;

use CloudCreativity\Modules\Application\DomainEventDispatching\DeferredDispatcherInterface;
use CloudCreativity\Modules\Application\InboundEventBus\Middleware\FlushDeferredEvents;
use CloudCreativity\Modules\Contracts\Application\Messages\IntegrationEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FlushDeferredEventsTest extends TestCase
{
    /**
     * @var MockObject&DeferredDispatcherInterface
     */
    private DeferredDispatcherInterface&MockObject $dispatcher;

    /**
     * @var FlushDeferredEvents
     */
    private FlushDeferredEvents $middleware;

    /**
     * @var array<string>
     */
    private array $sequence = [];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new FlushDeferredEvents(
            $this->dispatcher = $this->createMock(DeferredDispatcherInterface::class),
        );
    }

    /**
     * @return void
     */
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


    /**
     * @return void
     */
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
