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

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\EventBus;

use CloudCreativity\Modules\Infrastructure\EventBus\DelegatedPublisher;
use CloudCreativity\Modules\IntegrationEvents\IntegrationEventInterface;
use PHPUnit\Framework\TestCase;

class DelegatedPublisherTest extends TestCase
{
    /**
     * @return void
     */
    public function testItInvokesMethodsOnInnerHandler(): void
    {
        $event = new TestIntegrationEvent();
        $innerHandler = $this->createMock(TestPublisher::class);

        $innerHandler
            ->expects($this->once())
            ->method('publish')
            ->with($this->identicalTo($event));

        $innerHandler
            ->method('middleware')
            ->willReturn($middleware = ['Middleware1', 'Middleware2']);

        $publisher = new DelegatedPublisher($innerHandler);
        $publisher->publish($event);

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

        $publisher = new DelegatedPublisher($fn);
        $publisher->publish($event);

        $this->assertTrue($called);
        $this->assertSame([], $publisher->middleware());
    }
}
