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

final readonly class AddCommandHandler
{
    public function __construct(private int $c = 0)
    {
    }

    /**
     * Execute the command.
     *
     * @return Result<int>
     */
    public function execute(AddCommand $command): Result
    {
        return Result::ok($command->a + $command->b + $this->c);
    }
}
