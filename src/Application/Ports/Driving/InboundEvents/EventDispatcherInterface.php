<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Ports\Driving\InboundEvents;

use CloudCreativity\Modules\Application\Messages\IntegrationEventInterface;

interface EventDispatcherInterface
{
    /**
     * Dispatch an inbound integration event.
     *
     * @param IntegrationEventInterface $event
     * @return void
     */
    public function dispatch(IntegrationEventInterface $event): void;
}
