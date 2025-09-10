<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Application\DomainEventDispatching;

use Closure;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;

interface DomainEventMiddleware
{
    /**
     * Handle the domain event.
     *
     * @param Closure(DomainEvent): void $next
     */
    public function __invoke(DomainEvent $event, Closure $next): void;
}
