<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit;

use Closure;

final class Contracts
{
    /**
     * Assert that the provided precondition is true.
     *
     * @param Closure(): string|string $message
     * @phpstan-assert true $precondition
     */
    public static function assert(bool $precondition, Closure|string $message = ''): void
    {
        if ($precondition === false) {
            throw new ContractException(is_string($message) ? $message : $message());
        }
    }

    /**
     * Contracts constructor.
     */
    private function __construct()
    {
    }
}
