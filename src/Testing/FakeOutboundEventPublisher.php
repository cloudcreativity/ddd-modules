<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Testing;

use CloudCreativity\Modules\Contracts\Application\Ports\Driven\OutboundEventPublisher;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\IntegrationEvent;
use Countable;
use LogicException;

class FakeOutboundEventPublisher implements OutboundEventPublisher, Countable
{
    /**
     * @var list<IntegrationEvent>
     */
    public array $events = [];

    /**
     * @inheritDoc
     */
    public function publish(IntegrationEvent $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->events);
    }

    /**
     * Expect a single event to be published and return it.
     *
     * @return IntegrationEvent
     */
    public function sole(): IntegrationEvent
    {
        if (count($this->events) === 1) {
            return $this->events[0];
        }

        throw new LogicException(sprintf(
            'Expected one event to be published but there are %d events.',
            count($this->events),
        ));
    }
}
