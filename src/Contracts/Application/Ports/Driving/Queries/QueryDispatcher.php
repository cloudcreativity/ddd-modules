<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Application\Ports\Driving\Queries;

use CloudCreativity\Modules\Contracts\Application\Messages\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

interface QueryDispatcher
{
    /**
     * Dispatch the given query.
     *
     * @param Query $query
     * @return Result<mixed>
     */
    public function dispatch(Query $query): Result;
}
