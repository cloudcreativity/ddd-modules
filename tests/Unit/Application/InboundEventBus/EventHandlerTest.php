<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\InboundEventBus;

use CloudCreativity\Modules\Application\InboundEventBus\EventHandler;
use PHPUnit\Framework\TestCase;

class EventHandlerTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $event = new TestInboundEvent();

        $innerHandler = $this->createMock(TestEventHandler::class);
        $innerHandler
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($event));

        $innerHandler
            ->expects($this->once())
            ->method('middleware')
            ->willReturn($middleware = ['Middleware1', 'Middleware2']);

        $handler = new EventHandler($innerHandler);
        $handler($event);

        $this->assertSame($middleware, $handler->middleware());
    }

    /**
     * @return void
     */
    public function testItDoesNotHaveExecuteMethod(): void
    {
        $handler = new EventHandler(new \DateTime());
        $event = new TestInboundEvent();
        $eventClass = $event::class;

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage(
            "Cannot dispatch \"{$eventClass}\" - handler \"DateTime\" does not have a handle method.",
        );

        $handler($event);
    }
}
