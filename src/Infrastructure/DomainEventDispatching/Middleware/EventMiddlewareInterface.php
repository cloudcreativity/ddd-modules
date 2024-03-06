<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\DomainEventDispatching\Middleware;

use Closure;
use CloudCreativity\Modules\Domain\Events\DomainEventInterface;

interface EventMiddlewareInterface
{
    /**
     * Handle the event.
     *
     * @param DomainEventInterface $event
     * @param Closure(DomainEventInterface): void $next
     * @return void
     */
    public function __invoke(DomainEventInterface $event, Closure $next): void;
}
