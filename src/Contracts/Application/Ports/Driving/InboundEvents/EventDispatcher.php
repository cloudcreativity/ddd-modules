<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Application\Ports\Driving\InboundEvents;

use CloudCreativity\Modules\Contracts\Application\Messages\IntegrationEvent;

interface EventDispatcher
{
    /**
     * Dispatch an inbound integration event.
     *
     * @param IntegrationEvent $event
     * @return void
     */
    public function dispatch(IntegrationEvent $event): void;
}
