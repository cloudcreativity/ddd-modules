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
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEventDispatcher;
use Countable;
use Generator;
use IteratorAggregate;
use LogicException;

/**
 * @implements ArrayAccess<int, DomainEvent>
 * @implements IteratorAggregate<int, DomainEvent>
 */
class FakeDomainEventDispatcher implements DomainEventDispatcher, Countable, ArrayAccess, IteratorAggregate
{
    /**
     * @var list<DomainEvent>
     */
    public array $events = [];

    public function dispatch(DomainEvent $event): void
    {
        $this->events[] = $event;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->events[$offset]);
    }

    public function offsetGet(mixed $offset): DomainEvent
    {
        return $this->events[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('Cannot set domain events.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Cannot unset domain events.');
    }

    /**
     * @return Generator<int, DomainEvent>
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
     * Get the first event in the list, but only if exactly one event exists. Otherwise, throw an exception.
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
