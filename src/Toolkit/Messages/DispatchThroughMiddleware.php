<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Messages;

interface DispatchThroughMiddleware
{
    /**
     * Get the middleware to dispatch the message through.
     *
     * @return array<callable|string>
     */
    public function middleware(): array;
}
