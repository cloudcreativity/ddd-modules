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
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

final class TearDownAfterDispatch implements JobMiddlewareInterface
{
    /**
     * TearDownAfterDispatch constructor.
     *
     * @param Closure(): void $callback
     */
    public function __construct(private readonly Closure $callback)
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(QueueJobInterface $job, Closure $next): ResultInterface
    {
        try {
            return $next($job);
        } finally {
            ($this->callback)();
        }
    }
}
