<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\EventBus\Middleware;

use Closure;
use CloudCreativity\Modules\Infrastructure\UnitOfWork\UnitOfWorkManagerInterface;
use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;

final class NotifyInUnitOfWork implements EventBusMiddlewareInterface
{
    /**
     * NotifyInUnitOfWork constructor.
     *
     * @param UnitOfWorkManagerInterface $unitOfWorkManager
     * @param int $attempts
     */
    public function __construct(
        private readonly UnitOfWorkManagerInterface $unitOfWorkManager,
        private readonly int $attempts = 1,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(IntegrationEventInterface $event, Closure $next): void
    {
        $this->unitOfWorkManager->execute(
            static function () use ($event, $next): void {
                $next($event);
            },
            $this->attempts,
        );
    }
}
