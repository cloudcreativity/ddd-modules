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

interface QueryHandlerContainer
{
    /**
     * Get a query handler for the provided query name.
     *
     * @param string $queryClass
     * @return QueryHandler
     */
    public function get(string $queryClass): QueryHandler;
}
