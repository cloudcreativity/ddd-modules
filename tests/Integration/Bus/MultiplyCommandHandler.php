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

use CloudCreativity\Modules\Application\Bus\Middleware\ExecuteInUnitOfWork;
use CloudCreativity\Modules\Toolkit\Pipeline\Through;
use CloudCreativity\Modules\Toolkit\Result\Result;

#[Through(ExecuteInUnitOfWork::class)]
final class MultiplyCommandHandler
{
    /**
     * Execute the command.
     *
     * @return Result<int>
     */
    public function execute(MultiplyCommand $command): Result
    {
        return Result::ok($command->a * $command->b);
    }
}
