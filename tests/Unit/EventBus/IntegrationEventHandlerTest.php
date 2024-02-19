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
    public function testItInvokesMethodsOnInnerHandler(): void
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
