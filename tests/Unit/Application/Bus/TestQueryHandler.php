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
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result as IResult;
use CloudCreativity\Modules\Toolkit\Result\Result;

class TestQueryHandler implements TestQueryHandlerInterface, DispatchThroughMiddleware
{
    /**
     * @inheritDoc
     */
    public function execute(TestQuery $query): IResult
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
