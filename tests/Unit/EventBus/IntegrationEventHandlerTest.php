<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\EventBus;

use CloudCreativity\Modules\EventBus\IntegrationEventHandler;
use CloudCreativity\Modules\EventBus\IntegrationEventHandlerInterface;
use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;
use PHPUnit\Framework\TestCase;

class IntegrationEventHandlerTest extends TestCase
{
    /**
     * @return void
     */
    public function testItInvokesHandleMethodOnInnerHandler(): void
    {
        $event = new TestIntegrationEvent();
        $innerHandler = $this->createMock(TestIntegrationEventHandler::class);

        $innerHandler
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($event));

        $innerHandler
            ->method('middleware')
            ->willReturn($middleware = ['Middleware1', 'Middleware2']);

        $publisher = new IntegrationEventHandler($innerHandler);
        $publisher($event);

        $this->assertInstanceOf(IntegrationEventHandlerInterface::class, $publisher);
        $this->assertSame($middleware, $publisher->middleware());
    }

    /**
     * Allow the inner handler to use the "publish" method.
     *
     * This is useful where the handler is for a publisher, because it makes more sense
     * than "publish".
     *
     * @return void
     */
    public function testItInvokesPublishMethodOnInnerHandler(): void
    {
        $event = new TestIntegrationEvent();
        $innerHandler = new class () {
            public TestIntegrationEvent|null $published = null;

            public function publish(TestIntegrationEvent $event): void
            {
                $this->published = $event;
            }
        };

        $publisher = new IntegrationEventHandler($innerHandler);
        $publisher($event);

        $this->assertSame($event, $innerHandler->published);
    }

    /**
     * @return void
     */
    public function testItInvokesClosure(): void
    {
        $called = false;
        $event = new TestIntegrationEvent();
        $fn = function (IntegrationEventInterface $passed) use ($event, &$called): void {
            $this->assertSame($event, $passed);
            $called = true;
        };

        $publisher = new IntegrationEventHandler($fn);
        $publisher($event);

        $this->assertTrue($called);
        $this->assertSame([], $publisher->middleware());
    }
}
