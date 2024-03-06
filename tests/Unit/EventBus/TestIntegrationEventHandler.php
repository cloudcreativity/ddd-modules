<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\EventBus;

use CloudCreativity\Modules\Toolkit\Messages\DispatchThroughMiddleware;

class TestIntegrationEventHandler implements DispatchThroughMiddleware
{
    /**
     * Handle the integration event.
     *
     * @param TestIntegrationEvent $event
     * @return void
     */
    public function handle(TestIntegrationEvent $event): void
    {
        // no-op
    }

    /**
     * @inheritDoc
     */
    public function middleware(): array
    {
        return [];
    }
}
