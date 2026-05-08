<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Application\Ports;

use CloudCreativity\Modules\Contracts\Messaging\IntegrationEvent;

interface OutboundEventPublisher
{
    /**
     * Publish an outbound integration event.
     */
    public function publish(IntegrationEvent $event): void;
}
