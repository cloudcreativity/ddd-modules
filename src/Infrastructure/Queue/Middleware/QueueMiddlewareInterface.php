<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Queue\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Application\Messages\Command;

interface QueueMiddlewareInterface
{
    /**
     * Handle the command message being queued.
     *
     * @param Command $command
     * @param Closure(Command): void $next
     * @return void
     */
    public function __invoke(Command $command, Closure $next): void;
}
