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

final readonly class SetupBeforeDispatch implements BusMiddleware
{
    /**
     * @param Closure(): ?Closure(): void $callback
     */
    public function __construct(private Closure $callback)
    {
    }

    public function __invoke(Message $message, Closure $next): ?Result
    {
        $tearDown = ($this->callback)();

        assert(
            $tearDown === null || $tearDown instanceof Closure,
            'Expecting setup function to return null or a teardown closure.',
        );

        try {
            return $next($message);
        } finally {
            if ($tearDown) {
                $tearDown();
            }
        }
    }
}
