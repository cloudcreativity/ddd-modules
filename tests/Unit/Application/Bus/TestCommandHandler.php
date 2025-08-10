<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\Bus;

use CloudCreativity\Modules\Contracts\Application\Messages\DispatchThroughMiddleware;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use CloudCreativity\Modules\Toolkit\Result\Result;

class TestCommandHandler implements DispatchThroughMiddleware
{
    /**
     * Execute the command.
     *
     * @param TestCommand $command
     * @return Result<Identifier|null>
     */
    public function execute(TestCommand $command): Result
    {
        if ($command->fail) {
            return Result::fail('It failed!');
        }

        return Result::ok(Uuid::random());
    }

    /**
     * @inheritDoc
     */
    public function middleware(): array
    {
        return [];
    }
}
