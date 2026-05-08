<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Integration\Infrastructure\Queue;

use CloudCreativity\Modules\Contracts\Messaging\Command;

final class TestDefaultEnqueuer
{
    /**
     * @var array<Command>
     */
    public array $queued = [];

    public function push(Command $command): void
    {
        $this->queued[] = $command;
    }
}
