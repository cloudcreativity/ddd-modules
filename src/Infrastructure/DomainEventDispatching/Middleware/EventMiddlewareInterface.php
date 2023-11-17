<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Infrastructure\DomainEventDispatching\Middleware;

use Closure;
use CloudCreativity\BalancedEvent\Common\Domain\Events\DomainEventInterface;

interface EventMiddlewareInterface
{
    /**
     * Handle the event.
     *
     * @param DomainEventInterface $event
     * @param Closure $next
     * @return void
     */
    public function __invoke(DomainEventInterface $event, Closure $next): void;
}
