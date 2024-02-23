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
use CloudCreativity\Modules\EventBus\IntegrationEventHandlerContainer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class IntegrationEventHandlerContainerTest extends TestCase
{
    /**
     * @return void
     */
    public function testItBindsPublishers(): void
    {
        $a = new TestIntegrationEventHandler();
        $b = $this->createMock(TestIntegrationEventHandler::class);
        $c = fn () => true;
        $d = fn () => false;

        $event1 = new class () extends TestIntegrationEvent {};
        $event2 = new class () extends TestIntegrationEvent {};
        $event3 = new class () extends TestIntegrationEvent {};
        $event4 = new class () extends TestIntegrationEvent {};
        $event5 = new class () extends TestIntegrationEvent {};

        $container = new IntegrationEventHandlerContainer();
        $container->bind($event1::class, fn () => $a);
        $container->bind($event2::class, fn () => $b);
        $container->register($event3::class, $c);
        $container->register($event4::class, $d);

        $this->assertEquals(new IntegrationEventHandler($a), $container->get($event1::class));
        $this->assertEquals(new IntegrationEventHandler($b), $container->get($event2::class));
        $this->assertEquals(new IntegrationEventHandler($c), $container->get($event3::class));
        $this->assertEquals(new IntegrationEventHandler($d), $container->get($event4::class));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No handler bound for integration event: ' . $event5::class);

        $container->get($event5::class);
    }
}