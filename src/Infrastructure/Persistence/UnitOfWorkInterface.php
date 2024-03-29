<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Persistence;

use Closure;

interface UnitOfWorkInterface
{
    /**
     * Execute the callback in a unit of work.
     *
     * @template TReturn
     * @param Closure(): TReturn $callback
     * @param int $attempts
     * @return TReturn
     */
    public function execute(Closure $callback, int $attempts = 1): mixed;
}
