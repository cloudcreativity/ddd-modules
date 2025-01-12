<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\InboundEventBus\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Application\InboundEventBus\InboundEventMiddleware;
use CloudCreativity\Modules\Contracts\Application\UnitOfWork\UnitOfWorkManager;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\IntegrationEvent;

final class HandleInUnitOfWork implements InboundEventMiddleware
{
    /**
     * HandleInUnitOfWork constructor.
     *
     * @param UnitOfWorkManager $unitOfWorkManager
     * @param int $attempts
     */
    public function __construct(
        private readonly UnitOfWorkManager $unitOfWorkManager,
        private readonly int $attempts = 1,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(IntegrationEvent $event, Closure $next): void
    {
        $this->unitOfWorkManager->execute(
            static function () use ($event, $next): void {
                $next($event);
            },
            $this->attempts,
        );
    }
}
