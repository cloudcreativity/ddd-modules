<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Application\DomainEventDispatching\DeferredDispatcher;
use CloudCreativity\Modules\Contracts\Bus\Middleware\BusMiddleware;
use CloudCreativity\Modules\Contracts\Messaging\Message;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;
use Throwable;

final readonly class FlushDeferredEvents implements BusMiddleware
{
    public function __construct(private DeferredDispatcher $dispatcher)
    {
    }

    public function __invoke(Message $message, Closure $next): ?Result
    {
        try {
            $result = $next($message);
        } catch (Throwable $ex) {
            $this->dispatcher->forget();
            throw $ex;
        }

        if ($result?->didFail()) {
            $this->dispatcher->forget();
        } else {
            $this->dispatcher->flush();
        }

        return $result;
    }
}
