<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Bus\Middleware;

use Closure;
use CloudCreativity\BalancedEvent\Common\Bus\CommandInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ResultInterface;

interface CommandMiddlewareInterface
{
    /**
     * Handle the command.
     *
     * @param CommandInterface $command
     * @param Closure $next
     * @return ResultInterface
     */
    public function __invoke(CommandInterface $command, Closure $next): ResultInterface;
}
