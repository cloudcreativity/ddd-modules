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
use CloudCreativity\Modules\Toolkit\Messages\QueryInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

interface QueryMiddlewareInterface
{
    /**
     * Handle the query.
     *
     * @param QueryInterface $query
     * @param Closure(QueryInterface): ResultInterface<mixed> $next
     * @return ResultInterface<mixed>
     */
    public function __invoke(QueryInterface $query, Closure $next): ResultInterface;
}
