<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Identifiers;

use Stringable;

interface IdentifierInterface extends Stringable
{
    /**
     * Is the identifier the same as the provided identifier?
     *
     * @param IdentifierInterface|null $other
     * @return bool
     */
    public function is(?self $other): bool;

    /**
     * Fluent to-string method.
     *
     * @return string
     */
    public function toString(): string;

    /**
     * Get the value for the identifier when it is being used as an array key.
     *
     * @return array-key
     */
    public function key(): string|int;

    /**
     * Get the value to use when adding the identifier to log context.
     *
     * @return mixed
     */
    public function context(): mixed;
}
