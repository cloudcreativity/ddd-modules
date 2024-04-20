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
use CloudCreativity\Modules\Infrastructure\Persistence\AbortOnFailureException;
use CloudCreativity\Modules\Infrastructure\Persistence\UnitOfWorkManagerInterface;
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

final class ExecuteInUnitOfWork implements JobMiddlewareInterface
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
    public function __invoke(QueueJobInterface $job, Closure $next): ResultInterface
    {
        try {
            return $this->unitOfWorkManager->execute(
                static function () use ($job, $next): ResultInterface {
                    $res = $next($job);
                    return $res->didSucceed() ? $res : throw new AbortOnFailureException($res);
                },
                $this->attempts,
            );
        } catch (AbortOnFailureException $ex) {
            return $ex->getResult();
        }
    }
}
