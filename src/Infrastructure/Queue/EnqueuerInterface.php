<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Queue;

use CloudCreativity\Modules\Contracts\Application\Messages\Command;

interface EnqueuerInterface
{
    /**
     * Put the command on the queue.
     *
     * @param Command $queueable
     * @return void
     */
    public function __invoke(Command $queueable): void;
}
