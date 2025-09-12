<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Infrastructure\OutboundEventBus;

use CloudCreativity\Modules\Contracts\Toolkit\Messages\IntegrationEvent;

interface PublisherHandler
{
    /**
     * Handle the integration event.
     */
    public function __invoke(IntegrationEvent $event): void;
}
