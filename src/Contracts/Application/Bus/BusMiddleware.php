<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Application\Bus;

use Closure;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

interface BusMiddleware
{
    /**
     * Handle the command or query.
     *
     * @param Command|Query $message
     * @param Closure(Command|Query): Result<mixed> $next
     * @return Result<mixed>
     */
    public function __invoke(Command|Query $message, Closure $next): Result;
}
