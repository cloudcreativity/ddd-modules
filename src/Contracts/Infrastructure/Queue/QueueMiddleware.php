<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Infrastructure\Queue;

use Closure;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;

interface QueueMiddleware
{
    /**
     * Handle the command being queued.
     *
     * @param Command $command
     * @param Closure(Command): void $next
     * @return void
     */
    public function __invoke(Command $command, Closure $next): void;
}
