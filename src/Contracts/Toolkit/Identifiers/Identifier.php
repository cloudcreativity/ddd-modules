<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Toolkit\Identifiers;

use CloudCreativity\Modules\Contracts\Toolkit\Loggable\Contextual;
use Stringable;

interface Identifier extends Stringable, Contextual
{
    /**
     * Is the identifier the same as the provided identifier?
     */
    public function is(?self $other): bool;

    /**
     * Is the identifier any of the provided identifiers?
     */
    public function any(?self ...$others): bool;

    /**
     * Fluent to-string method.
     */
    public function toString(): string;

    /**
     * Get the value for the identifier when it is being used as an array key.
     *
     * @return array-key
     */
    public function key(): int|string;
}
