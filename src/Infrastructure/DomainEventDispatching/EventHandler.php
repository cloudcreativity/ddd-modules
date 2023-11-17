<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Infrastructure\DomainEventDispatching;

use Closure;
use CloudCreativity\BalancedEvent\Common\Domain\Events\DomainEventInterface;

class EventHandler
{
    /**
     * EventHandler constructor.
     *
     * @param object $listener
     */
    public function __construct(private readonly object $listener)
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
     * @return bool
     */
    public function beforeCommit(): bool
    {
        return $this->listener instanceof DispatchBeforeCommit;
    }

    /**
     * Should the handler be executed after the transaction is committed?
     *
     * @return bool
     */
    public function afterCommit(): bool
    {
        return $this->listener instanceof DispatchAfterCommit;
    }

    /**
     * Execute the handler.
     *
     * @param DomainEventInterface $event
     * @return void
     */
    public function __invoke(DomainEventInterface $event): void
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
