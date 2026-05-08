<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Testing;

use Closure;
use CloudCreativity\Modules\Contracts\Application\Ports\UnitOfWork;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class FakeUnitOfWork implements UnitOfWork
{
    public int $attempts = 0;

    public int $commits = 0;

    public int $rollbacks = 0;

    /**
     * @var list<string>
     */
    public array $sequence = [];

    public function __construct(public FakeExceptionReporter $exceptions = new FakeExceptionReporter())
    {
    }

    public function execute(Closure $callback, int $attempts = 1): mixed
    {
        if ($attempts < 1) {
            throw new InvalidArgumentException('The number of attempts must be at least 1.');
        }

        for ($i = 1; $i <= $attempts; $i++) {
            try {
                $this->sequence[] = 'attempt:' . $i;
                $this->attempts++;
                $result = $callback();
                $this->sequence[] = 'commit:' . $i;
                $this->commits++;
                return $result;
            } catch (Throwable $ex) {
                $this->sequence[] = 'rollback:' . $i;
                $this->rollbacks++;
                $this->exceptions->report($ex);

                if ($i === $attempts) {
                    throw $ex;
                }
            }
        }

        throw new RuntimeException('Failed to execute unit of work.');
    }
}
