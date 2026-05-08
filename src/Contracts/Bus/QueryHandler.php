<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Bus;

use CloudCreativity\Modules\Contracts\Messaging\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

interface QueryHandler extends DispatchThroughMiddleware
{
    /**
     * Execute the query.
     *
     * @return Result<mixed>
     */
    public function __invoke(Query $query): Result;
}
