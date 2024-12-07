<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Toolkit\Iterables;

use Countable;
use IteratorAggregate;

/**
 * @template TValue
 * @extends IteratorAggregate<int, TValue>
 */
interface ListIterator extends IteratorAggregate, Countable
{
    /**
     * Get the list as an array.
     *
     * @return list<TValue>
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
