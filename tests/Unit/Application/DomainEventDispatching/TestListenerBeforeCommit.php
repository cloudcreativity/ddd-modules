<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\DomainEventDispatching;

use CloudCreativity\Modules\Application\UnitOfWork\DispatchBeforeCommit;
use CloudCreativity\Modules\Domain\Events\DomainEventInterface;

class TestListenerBeforeCommit implements DispatchBeforeCommit
{
    /**
     * Handle the event.
     *
     * @param DomainEventInterface $event
     * @return void
     */
    public function handle(DomainEventInterface $event): void
    {
        // no-op
    }
}
