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

namespace CloudCreativity\Modules\EventBus;

use CloudCreativity\Modules\EventBus\Inbound\NotifierInterface;
use CloudCreativity\Modules\EventBus\Outbound\PublisherInterface;
use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;
use RuntimeException;

class EventBus implements EventBusInterface
{
    /**
     * EventBus constructor.
     *
     * @param PublisherInterface|null $publisher
     * @param NotifierInterface|null $notifier
     */
    public function __construct(
        private readonly ?PublisherInterface $publisher = null,
        private readonly ?NotifierInterface $notifier = null,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function publish(IntegrationEventInterface $event): void
    {
        if ($this->publisher) {
            $this->publisher->publish($event);
            return;
        }

        throw new RuntimeException(
            'Event bus must have a publisher instance to publish an outbound integration event.',
        );
    }

    /**
     * @inheritDoc
     */
    public function notify(IntegrationEventInterface $event): void
    {
        if ($this->notifier) {
            $this->notifier->notify($event);
            return;
        }

        throw new RuntimeException(
            'Event bus must have a notifier instance to receive an inbound integration event.',
        );
    }
}
