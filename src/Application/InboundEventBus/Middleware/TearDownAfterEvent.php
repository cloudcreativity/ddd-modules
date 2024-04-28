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
use CloudCreativity\Modules\Application\Messages\IntegrationEventInterface;

final class TearDownAfterEvent implements InboundEventMiddlewareInterface
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
    public function __invoke(IntegrationEventInterface $event, Closure $next): void
    {
        try {
            $next($event);
        } finally {
            ($this->callback)();
        }
    }
}
