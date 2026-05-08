<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Infrastructure\Queue;

use Closure;
use CloudCreativity\Modules\Contracts\Messaging\Command;

interface QueueMiddleware
{
    /**
     * Handle the command being queued.
     *
     * @param Closure(Command): void $next
     */
    public function __invoke(Command $command, Closure $next): void;
}
