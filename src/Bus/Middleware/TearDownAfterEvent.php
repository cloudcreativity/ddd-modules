<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Bus\Middleware\IntegrationEventMiddleware;
use CloudCreativity\Modules\Contracts\Messaging\IntegrationEvent;

final readonly class TearDownAfterEvent implements IntegrationEventMiddleware
{
    /**
     * @param Closure(): void $callback
     */
    public function __construct(private Closure $callback)
    {
    }

    public function __invoke(IntegrationEvent $event, Closure $next): void
    {
        try {
            $next($event);
        } finally {
            ($this->callback)();
        }
    }
}
