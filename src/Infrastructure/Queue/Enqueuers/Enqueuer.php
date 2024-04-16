<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Queue\Enqueuers;

use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;

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
    public function __invoke(CommandInterface|QueueJobInterface $queueable): void
    {
        assert(method_exists($this->enqueuer, 'push'), sprintf(
            'Cannot queue "%s" - enqueuer "%s" does not have a push method.',
            $queueable::class,
            $this->enqueuer::class,
        ));

        $this->enqueuer->push($queueable);
    }
}
