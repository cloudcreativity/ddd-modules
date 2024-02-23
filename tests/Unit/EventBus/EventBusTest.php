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

use CloudCreativity\Modules\EventBus\EventBus;
use CloudCreativity\Modules\EventBus\Inbound\NotifierInterface;
use CloudCreativity\Modules\EventBus\Outbound\PublisherInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EventBusTest extends TestCase
{
    /**
     * @return void
     */
    public function testItPublishesEvent(): void
    {
        $bus = new EventBus(
            publisher: $publisher = $this->createMock(PublisherInterface::class),
        );

        $publisher
            ->expects($this->once())
            ->method('publish')
            ->with($this->identicalTo($event = new TestIntegrationEvent()));

        $bus->publish($event);
    }

    /**
     * @return void
     */
    public function testItCannotPublishEvent(): void
    {
        $bus = new EventBus();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Event bus must have a publisher instance to publish an outbound integration event.',
        );

        $bus->publish(new TestIntegrationEvent());
    }

    /**
     * @return void
     */
    public function testItNotifiesEvent(): void
    {
        $bus = new EventBus(
            notifier: $receiver = $this->createMock(NotifierInterface::class),
        );

        $receiver
            ->expects($this->once())
            ->method('notify')
            ->with($this->identicalTo($event = new TestIntegrationEvent()));

        $bus->notify($event);
    }

    /**
     * @return void
     */
    public function testItCannotNotifyEvent(): void
    {
        $bus = new EventBus();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Event bus must have a notifier instance to receive an inbound integration event.',
        );

        $bus->notify(new TestIntegrationEvent());
    }
}
