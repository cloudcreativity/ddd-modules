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

use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEventDispatcher;
use LogicException;

class FakeDomainEventDispatcher implements DomainEventDispatcher
{
    /**
     * @var array<DomainEvent>
     */
    public array $events = [];

    /**
     * @inheritDoc
     */
    public function dispatch(DomainEvent $event): void
    {
        $this->events[] = $event;
    }

    /**
     * Expect a single event to be dispatched and return it.
     *
     * @return DomainEvent
     */
    public function sole(): DomainEvent
    {
        if (count($this->events) === 1) {
            return $this->events[0];
        }

        throw new LogicException(sprintf(
            'Expected one event to be dispatched but there are %d events.',
            count($this->events),
        ));
    }
}
