<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\EventBus\Outbound;

use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;

interface PublisherInterface
{
    /**
     * Publish an outbound integration event.
     *
     * @param IntegrationEventInterface $event
     * @return void
     */
    public function publish(IntegrationEventInterface $event): void;
}
