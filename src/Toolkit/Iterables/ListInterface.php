<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Iterables;

use Countable;
use IteratorAggregate;

/**
 * @template TValue
 * @extends IteratorAggregate<TValue>
 */
interface ListInterface extends IteratorAggregate, Countable
{
    /**
     * Get the list as an array.
     *
     * @return array<TValue>
     */
    public function all(): array;

    /**
     * Is the list empty?
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Is the list not empty?
     *
     * @return bool
     */
    public function isNotEmpty(): bool;
}
