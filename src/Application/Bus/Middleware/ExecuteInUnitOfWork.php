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
use CloudCreativity\Modules\Application\Bus\AbortOnFailureException;
use CloudCreativity\Modules\Contracts\Application\UnitOfWork\UnitOfWorkManager;
use CloudCreativity\Modules\Contracts\Bus\Middleware\BusMiddleware;
use CloudCreativity\Modules\Contracts\Messaging\Message;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

final readonly class ExecuteInUnitOfWork implements BusMiddleware
{
    /**
     * @param int<1, max> $attempts
     */
    public function __construct(
        private UnitOfWorkManager $unitOfWorkManager,
        private int $attempts = 1,
    ) {
    }

    public function __invoke(Message $message, Closure $next): ?Result
    {
        try {
            return $this->unitOfWorkManager->execute(
                static function () use ($message, $next): ?Result {
                    $res = $next($message);
                    if ($res?->didFail()) {
                        throw new AbortOnFailureException($res);
                    }
                    return $res;
                },
                $this->attempts,
            );
        } catch (AbortOnFailureException $ex) {
            return $ex->getResult();
        }
    }
}
