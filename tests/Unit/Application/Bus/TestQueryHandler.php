<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\Bus;

use CloudCreativity\Modules\Contracts\Application\Messages\DispatchThroughMiddleware;
use CloudCreativity\Modules\Toolkit\Result\Result;

class TestQueryHandler implements DispatchThroughMiddleware
{
    /**
     * Execute the query.
     *
     * @param TestQuery $query
     * @return Result<int>
     */
    public function execute(TestQuery $query): Result
    {
        return Result::ok(99);
    }

    /**
     * @inheritDoc
     */
    public function middleware(): array
    {
        return [];
    }
}
