<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Infrastructure\Queue;

use CloudCreativity\Modules\Contracts\Application\Messages\Command;

interface EnqueuerContainer
{
    /**
     * Get an enqueuer for the provided command.
     *
     * @param class-string<Command> $command
     * @return Enqueuer
     */
    public function get(string $command): Enqueuer;
}
