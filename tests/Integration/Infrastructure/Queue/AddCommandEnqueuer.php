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

use CloudCreativity\Modules\Tests\Integration\Bus\AddCommand;

final class AddCommandEnqueuer
{
    /**
     * @var array<AddCommand>
     */
    public array $queued = [];

    public function push(AddCommand $command): void
    {
        $this->queued[] = $command;
    }
}
