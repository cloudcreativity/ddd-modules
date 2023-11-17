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

interface UnitOfWorkManagerInterface
{
    /**
     * Execute the provided closure within a unit of work.
     *
     * @param Closure $callback
     * @param int $attempts
     * @return mixed
     */
    public function execute(Closure $callback, int $attempts = 1): mixed;

    /**
     * Register a callback to be executed before the unit of work is committed.
     *
     * @param callable $callback
     * @return void
     */
    public function beforeCommit(callable $callback): void;

    /**
     * Register a callback to be executed after the unit of work is committed.
     *
     * @param callable $callback
     * @return void
     */
    public function afterCommit(callable $callback): void;
}
