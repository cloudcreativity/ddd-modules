<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
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
