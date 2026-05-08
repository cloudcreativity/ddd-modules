<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Bus\Middleware\BusMiddleware;
use CloudCreativity\Modules\Contracts\Messaging\Message;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

final readonly class TearDownAfterDispatch implements BusMiddleware
{
    /**
     * @param Closure(): void $callback
     */
    public function __construct(private Closure $callback)
    {
    }

    public function __invoke(Message $message, Closure $next): ?Result
    {
        try {
            return $next($message);
        } finally {
            ($this->callback)();
        }
    }
}
