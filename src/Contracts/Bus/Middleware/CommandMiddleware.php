<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Messaging\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

interface CommandMiddleware
{
    /**
     * Handle the command.
     *
     * @param Closure(Command): Result<mixed> $next
     * @return Result<mixed>|null
     */
    public function __invoke(Command $command, Closure $next): ?Result;
}
