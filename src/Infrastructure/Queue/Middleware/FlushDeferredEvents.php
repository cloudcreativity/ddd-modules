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
use CloudCreativity\Modules\Infrastructure\DomainEventDispatching\DeferredDispatcherInterface;
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;
use Throwable;

final class FlushDeferredEvents implements JobMiddlewareInterface
{
    /**
     * FlushDeferredEvents constructor.
     *
     * @param DeferredDispatcherInterface $dispatcher
     */
    public function __construct(private readonly DeferredDispatcherInterface $dispatcher)
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(QueueJobInterface $job, Closure $next): ResultInterface
    {
        try {
            $result = $next($job);
        } catch (Throwable $ex) {
            $this->dispatcher->forget();
            throw $ex;
        }

        if ($result->didSucceed()) {
            $this->dispatcher->flush();
        } else {
            $this->dispatcher->forget();
        }

        return $result;
    }
}
