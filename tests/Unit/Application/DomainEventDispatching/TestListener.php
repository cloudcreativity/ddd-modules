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

use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;

class TestListener
{
    /**
     * Handle the event.
     *
     * @param DomainEvent $event
     * @return void
     */
    public function handle(DomainEvent $event): void
    {
        // no-op
    }
}
