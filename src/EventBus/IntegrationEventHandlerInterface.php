<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\EventBus;

use CloudCreativity\Modules\Toolkit\Messages\DispatchThroughMiddleware;
use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;

interface IntegrationEventHandlerInterface extends DispatchThroughMiddleware
{
    /**
     * Handle the integration event.
     *
     * @param IntegrationEventInterface $event
     * @return void
     */
    public function __invoke(IntegrationEventInterface $event): void;
}
