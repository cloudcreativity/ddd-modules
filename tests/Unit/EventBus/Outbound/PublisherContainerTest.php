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

namespace CloudCreativity\Modules\Tests\Unit\EventBus\Outbound;

use CloudCreativity\Modules\EventBus\Outbound\PublisherContainer;
use CloudCreativity\Modules\EventBus\Outbound\PublisherHandler;
use CloudCreativity\Modules\Tests\Unit\EventBus\TestIntegrationEvent;
use CloudCreativity\Modules\Tests\Unit\EventBus\TestPublisher;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PublisherContainerTest extends TestCase
{
    /**
     * @return void
     */
    public function testItBindsPublishers(): void
    {
        $a = new TestPublisher();
        $b = $this->createMock(TestPublisher::class);
        $c = fn () => true;
        $d = fn () => false;

        $event1 = new class () extends TestIntegrationEvent {};
        $event2 = new class () extends TestIntegrationEvent {};
        $event3 = new class () extends TestIntegrationEvent {};
        $event4 = new class () extends TestIntegrationEvent {};
        $event5 = new class () extends TestIntegrationEvent {};

        $container = new PublisherContainer();
        $container->bind($event1::class, fn () => $a);
        $container->bind($event2::class, fn () => $b);
        $container->register($event3::class, $c);
        $container->register($event4::class, $d);

        $this->assertEquals(new PublisherHandler($a), $container->get($event1::class));
        $this->assertEquals(new PublisherHandler($b), $container->get($event2::class));
        $this->assertEquals(new PublisherHandler($c), $container->get($event3::class));
        $this->assertEquals(new PublisherHandler($d), $container->get($event4::class));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No publisher bound for integration event: ' . $event5::class);

        $container->get($event5::class);
    }
}
