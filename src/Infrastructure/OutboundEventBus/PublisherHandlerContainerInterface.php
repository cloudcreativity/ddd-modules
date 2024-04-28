<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\OutboundEventBus;

use CloudCreativity\Modules\Application\Messages\IntegrationEventInterface;

interface PublisherHandlerContainerInterface
{
    /**
     * Get a handler for the specified integration event.
     *
     * @param class-string<IntegrationEventInterface> $eventName
     * @return PublisherHandlerInterface
     */
    public function get(string $eventName): PublisherHandlerInterface;
}
