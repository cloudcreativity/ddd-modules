<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Messaging\IntegrationEvent;

interface IntegrationEventMiddleware
{
    /**
     * Handle the inbound event.
     *
     * @param Closure(IntegrationEvent): void $next
     */
    public function __invoke(IntegrationEvent $event, Closure $next): void;
}
