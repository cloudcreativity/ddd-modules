<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\Bus;

use CloudCreativity\Modules\Contracts\Bus\DispatchThroughMiddleware;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use CloudCreativity\Modules\Toolkit\Result\Result;

abstract class TestCommandHandler implements DispatchThroughMiddleware
{
    /**
     * Execute the command.
     *
     * @return Result<Identifier|null>
     */
    public function execute(TestCommand $command): Result
    {
        if ($command->fail) {
            return Result::fail('It failed!');
        }

        return Result::ok(Uuid::random());
    }

    public function middleware(): array
    {
        return [];
    }
}
