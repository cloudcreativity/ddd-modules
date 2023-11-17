<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Infrastructure\Queue\Middleware;

use Closure;
use CloudCreativity\BalancedEvent\Common\Infrastructure\Queue\QueueableInterface;

interface QueueMiddlewareInterface
{
    /**
     * Handle the message being queued.
     *
     * @param QueueableInterface $queueable
     * @param Closure $next
     * @return void
     */
    public function __invoke(QueueableInterface $queueable, Closure $next): void;
}
