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

namespace CloudCreativity\Modules\Infrastructure\EventBus;

use CloudCreativity\Modules\Infrastructure\InfrastructureException;
use CloudCreativity\Modules\IntegrationEvents\IntegrationEventInterface;
use CloudCreativity\Modules\IntegrationEvents\PublisherInterface;

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

        throw new InfrastructureException(
            'Event bus must have a publisher instance to publish integration events.',
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

        throw new InfrastructureException(
            'Event bus must have a notifier instance to notify integration event subscribers.',
        );
    }

    /**
     * @inheritDoc
     */
    public function subscribe(string $event, string|array $receiver): void
    {
        if ($this->notifier) {
            $this->notifier->subscribe($event, $receiver);
            return;
        }

        throw new InfrastructureException(
            'Event bus must have a notifier instance to subscribe to integration events.',
        );
    }
}
