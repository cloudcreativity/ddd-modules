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

final class Enqueuer implements EnqueuerInterface
{
    /**
     * Enqueuer constructor.
     *
     * @param object $enqueuer
     */
    public function __construct(private readonly object $enqueuer)
    {
    }

    /**
     * @inheritDoc
     */
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
