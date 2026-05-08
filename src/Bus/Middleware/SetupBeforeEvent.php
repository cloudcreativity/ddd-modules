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

final readonly class SetupBeforeEvent implements IntegrationEventMiddleware
{
    /**
     * @param Closure(): ?Closure(): void $callback
     */
    public function __construct(private Closure $callback)
    {
    }

    public function __invoke(IntegrationEvent $event, Closure $next): void
    {
        $tearDown = ($this->callback)();

        assert(
            $tearDown === null || $tearDown instanceof Closure,
            'Expecting setup function to return null or a teardown closure.',
        );

        try {
            $next($event);
        } finally {
            if ($tearDown) {
                $tearDown();
            }
        }
    }
}
