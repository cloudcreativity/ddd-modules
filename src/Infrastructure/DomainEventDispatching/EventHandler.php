<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

namespace CloudCreativity\Modules\Infrastructure\DomainEventDispatching;

use Closure;
use CloudCreativity\Modules\Domain\Events\DomainEventInterface;

final class EventHandler
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
