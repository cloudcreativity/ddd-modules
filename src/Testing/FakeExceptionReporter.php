<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Testing;

use CloudCreativity\Modules\Contracts\Application\Ports\Driven\ExceptionReporter;
use Countable;
use LogicException;
use Throwable;

final class FakeExceptionReporter implements ExceptionReporter, Countable
{
    /**
     * @var list<Throwable>
     */
    public array $reported = [];

    public function report(Throwable $ex): void
    {
        $this->reported[] = $ex;
    }

    public function count(): int
    {
        return count($this->reported);
    }

    /**
     * Expect a single exception to be reported and return it.
     */
    public function sole(): Throwable
    {
        if (count($this->reported) === 1) {
            return $this->reported[0];
        }

        throw new LogicException(sprintf(
            'Expected one exception to be reported but there are %d exceptions.',
            count($this->reported),
        ));
    }
}
