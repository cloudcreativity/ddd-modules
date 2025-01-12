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
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

interface QueryMiddleware
{
    /**
     * Handle the query.
     *
     * @param Query $query
     * @param Closure(Query): Result<mixed> $next
     * @return Result<mixed>
     */
    public function __invoke(Query $query, Closure $next): Result;
}
