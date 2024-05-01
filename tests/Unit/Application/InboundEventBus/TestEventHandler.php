<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\InboundEventBus;

use CloudCreativity\Modules\Contracts\Application\Messages\DispatchThroughMiddleware;

class TestEventHandler implements DispatchThroughMiddleware
{
    /**
     * @param TestInboundEvent $command
     * @return void
     */
    public function handle(TestInboundEvent $command): void
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
