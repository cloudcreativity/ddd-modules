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

use Closure;
use CloudCreativity\Modules\Infrastructure\EventBus\Publisher;
use CloudCreativity\Modules\Infrastructure\EventBus\PublisherContainerInterface;
use CloudCreativity\Modules\Infrastructure\EventBus\PublishThroughMiddleware;
use CloudCreativity\Modules\IntegrationEvents\IntegrationEventInterface;
use CloudCreativity\Modules\IntegrationEvents\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PublisherTest extends TestCase
{
    /**
     * @var PublisherContainerInterface&MockObject
     */
    private PublisherContainerInterface $container;

    /**
     * @var Publisher
     */
    private Publisher $publisher;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(PublisherContainerInterface::class);
        $this->publisher = new Publisher($this->container);
    }

    /**
     * @return void
     */
    public function testPublish(): void
    {
        $event = new TestIntegrationEvent();

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with($event::class)
            ->willReturn($publisher = $this->createMock(PublisherInterface::class));

        $publisher
            ->expects($this->once())
            ->method('publish')
            ->with($this->identicalTo($event));

        $this->publisher->publish($event);
    }

    /**
     * @return void
     */
    public function testPublishWithMiddleware(): void
    {
        $event1 = new TestIntegrationEvent();
        $event2 = new TestIntegrationEvent();
        $event3 = new TestIntegrationEvent();

        $middleware1 = function ($actual, Closure $next) use ($event1, $event2) {
            $this->assertSame($event1, $actual);
            return $next($event2);
        };

        $middleware2 = function ($actual, Closure $next) use ($event2, $event3) {
            $this->assertSame($event2, $actual);
            return $next($event3);
        };

        $publisher = new class ($middleware2) implements PublisherInterface, PublishThroughMiddleware {
            public ?IntegrationEventInterface $passed = null;
            public function __construct(private Closure $middleware)
            {
            }

            /**
             * @inheritDoc
             */
            public function middleware(): array
            {
                return [$this->middleware];
            }

            /**
             * @inheritDoc
             */
            public function publish(IntegrationEventInterface $event): void
            {
                $this->passed = $event;
            }
        };

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with($event1::class)
            ->willReturn($publisher);

        $this->publisher->through([$middleware1]);
        $this->publisher->publish($event1);

        $this->assertSame($event3, $publisher->passed);
    }
}
