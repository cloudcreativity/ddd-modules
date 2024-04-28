<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\Queue;

use CloudCreativity\Modules\Application\Messages\CommandInterface;

class TestEnqueuer
{
    /**
     * @param CommandInterface $command
     * @return void
     */
    public function push(CommandInterface $command): void
    {
        // no-op
    }
}
