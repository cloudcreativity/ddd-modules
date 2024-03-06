<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\DomainEventDispatching;

use Closure;
use CloudCreativity\Modules\Domain\Events\DispatcherInterface as DomainDispatcherInterface;
use CloudCreativity\Modules\Domain\Events\DomainEventInterface;

interface DispatcherInterface extends DomainDispatcherInterface
{
    /**
     * Attach an event listener.
     *
     * @param class-string<DomainEventInterface> $event
     * @param string|Closure|array<string|Closure> $listener
     * @return void
     */
    public function listen(string $event, string|Closure|array $listener): void;
}
