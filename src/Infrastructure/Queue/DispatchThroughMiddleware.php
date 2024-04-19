<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Queue;

interface DispatchThroughMiddleware
{
    /**
     * Get the middleware to dispatch the queue job through.
     *
     * @return list<callable|string>
     */
    public function middleware(): array;
}