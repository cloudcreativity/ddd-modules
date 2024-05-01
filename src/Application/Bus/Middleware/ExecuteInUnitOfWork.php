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
use CloudCreativity\Modules\Application\Bus\Exceptions\AbortOnFailureException;
use CloudCreativity\Modules\Application\UnitOfWork\UnitOfWorkManagerInterface;
use CloudCreativity\Modules\Contracts\Application\Messages\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

final class ExecuteInUnitOfWork implements CommandMiddlewareInterface
{
    /**
     * ExecuteInUnitOfWork constructor.
     *
     * @param UnitOfWorkManagerInterface $unitOfWorkManager
     * @param int $attempts
     */
    public function __construct(
        private readonly UnitOfWorkManagerInterface $unitOfWorkManager,
        private readonly int $attempts = 1,
    ) {
    }

    /**
     * @inheritDoc
     */
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
