<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Infrastructure\DomainEventDispatching;

use Closure;
use CloudCreativity\BalancedEvent\Common\Domain\Events\DispatcherInterface as DomainDispatcherInterface;
use CloudCreativity\BalancedEvent\Common\Domain\Events\DomainEventInterface;

interface DispatcherInterface extends DomainDispatcherInterface
{
    /**
     * Attach an event listener.
     *
     * @param class-string<DomainEventInterface> $event
     * @param string|Closure|array<int,string|Closure> $listener
     * @return void
     */
    public function listen(string $event, string|Closure|array $listener): void;
}
