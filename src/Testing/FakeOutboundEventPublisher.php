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

use ArrayAccess;
use CloudCreativity\Modules\Contracts\Application\Ports\Driven\OutboundEventPublisher;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\IntegrationEvent;
use Countable;
use Generator;
use IteratorAggregate;
use LogicException;

/**
 * @implements ArrayAccess<int, IntegrationEvent>
 * @implements IteratorAggregate<int, IntegrationEvent>
 */
class FakeOutboundEventPublisher implements OutboundEventPublisher, Countable, ArrayAccess, IteratorAggregate
{
    /**
     * @var list<IntegrationEvent>
     */
    public array $events = [];

    public function publish(IntegrationEvent $event): void
    {
        $this->events[] = $event;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->events[$offset]);
    }

    public function offsetGet(mixed $offset): IntegrationEvent
    {
        return $this->events[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('Cannot set integration events.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Cannot unset integration events.');
    }

    /**
     * @return Generator<int, IntegrationEvent>
     */
    public function getIterator(): Generator
    {
        yield from $this->events;
    }

    public function count(): int
    {
        return count($this->events);
    }

    /**
     * Expect a single event to be published and return it.
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
