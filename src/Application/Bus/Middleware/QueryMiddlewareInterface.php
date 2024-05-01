<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Application\Messages\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

interface QueryMiddlewareInterface
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
