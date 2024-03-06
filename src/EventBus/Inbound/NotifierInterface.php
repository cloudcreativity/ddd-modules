<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\EventBus\Inbound;

use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;

interface NotifierInterface
{
    /**
     * Notify subscribers of an inbound integration event.
     *
     * @param IntegrationEventInterface $event
     * @return void
     */
    public function notify(IntegrationEventInterface $event): void;
}
