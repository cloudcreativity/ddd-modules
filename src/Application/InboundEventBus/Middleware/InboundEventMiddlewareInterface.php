<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\InboundEventBus\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Application\Messages\IntegrationEvent;

interface InboundEventMiddlewareInterface
{
    /**
     * Handle the inbound event.
     *
     * @param IntegrationEvent $event
     * @param Closure(IntegrationEvent): void $next
     * @return void
     */
    public function __invoke(IntegrationEvent $event, Closure $next): void;
}
