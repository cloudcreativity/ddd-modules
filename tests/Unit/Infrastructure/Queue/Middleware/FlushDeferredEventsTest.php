<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\Queue\Middleware;

use CloudCreativity\Modules\Infrastructure\DomainEventDispatching\DeferredDispatcherInterface;
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\FlushDeferredEvents;
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;
use CloudCreativity\Modules\Toolkit\Result\Result;
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
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->dispatcher, $this->middleware);
    }

    /**
     * @return void
     */
    public function testItFlushesDeferredEvents(): void
    {
        $job = $this->createMock(QueueJobInterface::class);
        $expected = Result::ok();

        $this->dispatcher
            ->expects($this->once())
            ->method('flush')
            ->willReturnCallback(function () {
                $this->sequence[] = 'flush';
            });

        $this->dispatcher
            ->expects($this->never())
            ->method('forget');

        $actual = ($this->middleware)($job, function ($in) use ($job, $expected) {
            $this->assertSame($job, $in);
            $this->sequence[] = 'next';
            return $expected;
        });

        $this->assertSame(['next', 'flush'], $this->sequence);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItForgetsDeferredEventsOnFailedResult(): void
    {
        $job = $this->createMock(QueueJobInterface::class);
        $expected = Result::failed('Something went wrong.');

        $this->dispatcher
            ->expects($this->once())
            ->method('forget')
            ->willReturnCallback(function () {
                $this->sequence[] = 'forget';
            });

        $this->dispatcher
            ->expects($this->never())
            ->method('flush');

        $actual = ($this->middleware)($job, function ($in) use ($job, $expected) {
            $this->assertSame($job, $in);
            $this->sequence[] = 'next';
            return $expected;
        });

        $this->assertSame(['next', 'forget'], $this->sequence);
    }


    /**
     * @return void
     */
    public function testItForgetsDeferredEventsOnException(): void
    {
        $job = $this->createMock(QueueJobInterface::class);
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
            ($this->middleware)($job, function ($in) use ($job, $expected) {
                $this->assertSame($job, $in);
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