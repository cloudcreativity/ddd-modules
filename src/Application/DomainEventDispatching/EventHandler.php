<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\DomainEventDispatching;

use Closure;
use CloudCreativity\Modules\Contracts\Application\UnitOfWork\DispatchAfterCommit;
use CloudCreativity\Modules\Contracts\Application\UnitOfWork\DispatchBeforeCommit;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;

final readonly class EventHandler
{
    public function __construct(private object $listener)
    {
        assert(
            !($this->listener instanceof DispatchBeforeCommit && $this->listener instanceof DispatchAfterCommit),
            sprintf(
                'Listener "%s" cannot be dispatched both before and after a unit of work is committed..',
                get_debug_type($this->listener),
            ),
        );
    }

    /**
     * Should the handler be executed before the transaction is committed?
     *
     */
    public function beforeCommit(): bool
    {
        return $this->listener instanceof DispatchBeforeCommit;
    }

    /**
     * Should the handler be executed after the transaction is committed?
     *
     */
    public function afterCommit(): bool
    {
        return $this->listener instanceof DispatchAfterCommit;
    }

    /**
     * Execute the listener.
     *
     */
    public function __invoke(DomainEvent $event): void
    {
        if ($this->listener instanceof Closure) {
            ($this->listener)($event);
            return;
        }

        assert(method_exists($this->listener, 'handle'), sprintf(
            'Listener "%s" is not an object with a handle method or a closure.',
            get_debug_type($this->listener),
        ));

        $this->listener->handle($event);
    }
}
