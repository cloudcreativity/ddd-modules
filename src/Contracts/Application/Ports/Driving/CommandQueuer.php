<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Application\Ports\Driving;

use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;

interface CommandQueuer
{
    /**
     * Queue a command for asynchronous dispatching.
     *
     * @param Command $command
     * @return void
     */
    public function queue(Command $command): void;
}
