<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Infrastructure\Queue;

use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;

interface EnqueuerContainer
{
    /**
     * Get an enqueuer for the provided command.
     *
     * @param class-string<Command> $command
     */
    public function get(string $command): Enqueuer;
}
