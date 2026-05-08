<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Integration\Bus;

use CloudCreativity\Modules\Toolkit\Result\Result;

final readonly class SubtractQueryHandler
{
    public function __construct(private int $c = 0)
    {
    }

    /**
     * Execute the query.
     *
     * @return Result<int>
     */
    public function execute(SubtractQuery $query): Result
    {
        return Result::ok($query->a - $query->b - $this->c);
    }
}
