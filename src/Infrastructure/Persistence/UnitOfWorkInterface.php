<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Infrastructure\Persistence;

use Closure;

interface UnitOfWorkInterface
{
    /**
     * Execute the callback in a unit of work.
     *
     * @param Closure $callback
     * @param int $attempts
     * @return mixed
     */
    public function execute(Closure $callback, int $attempts = 1): mixed;
}
