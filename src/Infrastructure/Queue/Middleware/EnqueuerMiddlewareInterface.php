<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Queue\Middleware;

use Closure;
use CloudCreativity\Modules\Infrastructure\Queue\QueueableInterface;

interface EnqueuerMiddlewareInterface
{
    /**
     * Handle the message being queued.
     *
     * @param QueueableInterface $queueable
     * @param Closure(QueueableInterface): void $next
     * @return void
     */
    public function __invoke(QueueableInterface $queueable, Closure $next): void;
}
