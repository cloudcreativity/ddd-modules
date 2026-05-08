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

interface QueryHandlerContainer
{
    /**
     * Get a query handler for the provided query name.
     *
     * @param class-string<Query> $queryClass
     */
    public function get(string $queryClass): QueryHandler;
}
