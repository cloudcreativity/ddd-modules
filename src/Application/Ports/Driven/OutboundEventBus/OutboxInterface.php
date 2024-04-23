<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Ports\Driven\OutboundEventBus;

use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;

interface OutboxInterface
{
    /**
     * Push an outbound integration event into the outbox.
     *
     * @param IntegrationEventInterface $event
     * @return void
     */
    public function push(IntegrationEventInterface $event): void;
}