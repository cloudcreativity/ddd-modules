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
use CloudCreativity\Modules\Contracts\Application\InboundEventBus\InboundEventMiddleware;
use CloudCreativity\Modules\Contracts\Application\Messages\IntegrationEvent;

final class TearDownAfterEvent implements InboundEventMiddleware
{
    /**
     * TearDownAfterEvent constructor.
     *
     * @param Closure(): void $callback
     */
    public function __construct(private readonly Closure $callback)
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(IntegrationEvent $event, Closure $next): void
    {
        try {
            $next($event);
        } finally {
            ($this->callback)();
        }
    }
}
