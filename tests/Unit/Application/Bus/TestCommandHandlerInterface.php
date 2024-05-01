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

use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

interface TestCommandHandlerInterface
{
    /**
     * @param TestCommand $command
     * @return Result<null>
     */
    public function execute(TestCommand $command): Result;
}
