<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Application\Ports\Driven\OutboundEventBus;

use CloudCreativity\Modules\Contracts\Application\Messages\IntegrationEvent;

interface EventPublisher
{
    /**
     * Publish an outbound integration event.
     *
     * @param IntegrationEvent $event
     * @return void
     */
    public function publish(IntegrationEvent $event): void;
}
