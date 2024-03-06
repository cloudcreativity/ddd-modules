<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
