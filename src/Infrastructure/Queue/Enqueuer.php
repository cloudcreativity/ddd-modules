<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Queue;

use CloudCreativity\Modules\Contracts\Infrastructure\Queue\Enqueuer as IEnqueuer;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;

final readonly class Enqueuer implements IEnqueuer
{
    public function __construct(private object $enqueuer)
    {
    }

    public function __invoke(Command $command): void
    {
        assert(method_exists($this->enqueuer, 'push'), sprintf(
            'Cannot queue "%s" - enqueuer "%s" does not have a push method.',
            $command::class,
            $this->enqueuer::class,
        ));

        $this->enqueuer->push($command);
    }
}
