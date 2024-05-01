<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Application\DomainEventDispatching\DeferredDispatcherInterface;
use CloudCreativity\Modules\Contracts\Application\Messages\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;
use Throwable;

final class FlushDeferredEvents implements CommandMiddlewareInterface
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
    public function __invoke(Command $command, Closure $next): Result
    {
        try {
            $result = $next($command);
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
