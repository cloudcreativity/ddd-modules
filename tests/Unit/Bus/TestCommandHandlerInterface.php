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

use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

interface TestCommandHandlerInterface
{
    /**
     * @param TestCommand $command
     * @return ResultInterface<null>
     */
    public function execute(TestCommand $command): ResultInterface;
}
