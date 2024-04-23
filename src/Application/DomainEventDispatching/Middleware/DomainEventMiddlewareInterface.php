<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\DomainEventDispatching\Middleware;

use Closure;
use CloudCreativity\Modules\Domain\Events\DomainEventInterface;

interface DomainEventMiddlewareInterface
{
    /**
     * Handle the domain event.
     *
     * @param DomainEventInterface $event
     * @param Closure(DomainEventInterface): void $next
     * @return void
     */
    public function __invoke(DomainEventInterface $event, Closure $next): void;
}
