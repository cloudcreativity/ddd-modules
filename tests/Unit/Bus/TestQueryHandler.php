<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Bus;

use CloudCreativity\Modules\Bus\DispatchThroughMiddleware;
use CloudCreativity\Modules\Toolkit\Result\Result;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

class TestQueryHandler implements TestQueryHandlerInterface, DispatchThroughMiddleware
{
    /**
     * @inheritDoc
     */
    public function execute(TestQuery $query): ResultInterface
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
