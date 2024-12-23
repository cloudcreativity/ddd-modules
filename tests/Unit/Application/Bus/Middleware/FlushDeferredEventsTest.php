<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\Bus\Middleware;

use CloudCreativity\Modules\Application\Bus\Middleware\FlushDeferredEvents;
use CloudCreativity\Modules\Contracts\Application\DomainEventDispatching\DeferredDispatcher;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;
use CloudCreativity\Modules\Toolkit\Result\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FlushDeferredEventsTest extends TestCase
{
    /**
     * @var MockObject&DeferredDispatcher
     */
    private DeferredDispatcher&MockObject $dispatcher;

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
            $this->dispatcher = $this->createMock(DeferredDispatcher::class),
        );
    }

    /**
     * @return void
     */
    public function testItFlushesDeferredEvents(): void
    {
        $command = $this->createMock(Command::class);
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

        $actual = ($this->middleware)($command, function ($in) use ($command, $expected) {
            $this->assertSame($command, $in);
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
        $command = $this->createMock(Command::class);
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

        $actual = ($this->middleware)($command, function ($in) use ($command, $expected) {
            $this->assertSame($command, $in);
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
        $command = $this->createMock(Command::class);
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
            ($this->middleware)($command, function ($in) use ($command, $expected) {
                $this->assertSame($command, $in);
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
