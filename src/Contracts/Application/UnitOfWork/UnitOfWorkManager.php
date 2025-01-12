<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Application\UnitOfWork;

use Closure;

interface UnitOfWorkManager
{
    /**
     * Execute the provided closure within a unit of work.
     *
     * @template TReturn
     * @param Closure(): TReturn $callback
     * @param int $attempts
     * @return TReturn
     */
    public function execute(Closure $callback, int $attempts = 1): mixed;

    /**
     * Register a callback to be executed before the unit of work is committed.
     *
     * @param callable(): void $callback
     * @return void
     */
    public function beforeCommit(callable $callback): void;

    /**
     * Register a callback to be executed after the unit of work is committed.
     *
     * @param callable(): void $callback
     * @return void
     */
    public function afterCommit(callable $callback): void;
}
