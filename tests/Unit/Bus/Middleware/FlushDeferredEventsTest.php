<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Bus\Middleware;

use CloudCreativity\Modules\Bus\Middleware\FlushDeferredEvents;
use CloudCreativity\Modules\Infrastructure\DomainEventDispatching\DeferredDispatcherInterface;
use CloudCreativity\Modules\Toolkit\Messages\MessageInterface;
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
    public function testItFlushesDeferredEvents(): void
    {
        $message = $this->createMock(MessageInterface::class);
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

        $actual = ($this->middleware)($message, function ($in) use ($message, $expected) {
            $this->assertSame($message, $in);
            $this->sequence[] = 'next';
            return $expected;
        });

        $this->assertSame(['next', 'flush'], $this->sequence);
    }

    /**
     * @return void
     */
    public function testItForgetsDeferredEventsOnFailedResult(): void
    {
        $message = $this->createMock(MessageInterface::class);
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

        $actual = ($this->middleware)($message, function ($in) use ($message, $expected) {
            $this->assertSame($message, $in);
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
        $message = $this->createMock(MessageInterface::class);
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
            ($this->middleware)($message, function ($in) use ($message, $expected) {
                $this->assertSame($message, $in);
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
