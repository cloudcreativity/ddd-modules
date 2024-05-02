<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Domain\Events;

interface DomainEventDispatcher
{
    /**
     * Dispatch a domain event.
     *
     * @param DomainEvent $event
     * @return void
     */
    public function dispatch(DomainEvent $event): void;
}
