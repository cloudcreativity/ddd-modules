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

    private ?int $expected = null;

    public function report(Throwable $ex): void
    {
        if ($this->expected === count($this->reported)) {
            throw $ex;
        }

        $this->reported[] = $ex;
    }

    /**
     * Expect no exceptions to be reported.
     *
     * Useful in tests where you do not expect any exceptions to be reported.
     * This will cause the fake reporter to re-throw any exceptions rather
     * than swallowing them.
     */
    public function none(): void
    {
        $this->expect(0);
    }

    /**
     * Set the amount of exceptions expected.
     *
     * This will cause the fake reporter to re-throw any exceptions
     * once the expected amount has been reported. This is primarily
     * useful for debugging, because if your test causes too many
     * exceptions, you will see the first unexpected exception.
     *
     * Note that this does not help if the reporter receives less
     * exceptions than expected. You should use assertions
     * in your test to verify the count of reported exceptions.
     */
    public function expect(int $count): void
    {
        $this->expected = $count;
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
