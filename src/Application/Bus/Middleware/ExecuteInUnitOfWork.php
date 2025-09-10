<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Application\Bus\Exceptions\AbortOnFailureException;
use CloudCreativity\Modules\Contracts\Application\Bus\CommandMiddleware;
use CloudCreativity\Modules\Contracts\Application\UnitOfWork\UnitOfWorkManager;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

final readonly class ExecuteInUnitOfWork implements CommandMiddleware
{
    /**
     * ExecuteInUnitOfWork constructor.
     *
     */
    public function __construct(
        private UnitOfWorkManager $unitOfWorkManager,
        private int $attempts = 1,
    ) {
    }

    public function __invoke(Command $command, Closure $next): Result
    {
        try {
            return $this->unitOfWorkManager->execute(
                static function () use ($command, $next): Result {
                    $res = $next($command);
                    return $res->didSucceed() ? $res : throw new AbortOnFailureException($res);
                },
                $this->attempts,
            );
        } catch (AbortOnFailureException $ex) {
            return $ex->getResult();
        }
    }
}
