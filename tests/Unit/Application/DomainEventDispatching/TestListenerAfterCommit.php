<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\DomainEventDispatching;

use CloudCreativity\Modules\Contracts\Application\UnitOfWork\DispatchAfterCommit;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;

class TestListenerAfterCommit implements DispatchAfterCommit
{
    /**
     * Handle the event.
     */
    public function handle(DomainEvent $event): void
    {
        // no-op
    }
}
