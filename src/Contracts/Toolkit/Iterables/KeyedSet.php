<?php

/*
 * Copyright 2025 Cloud Creativity Limited
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
 * @extends IteratorAggregate<string,TValue>
 */
interface KeyedSet extends IteratorAggregate, Countable
{
    /**
     * Get the keyed set as an array.
     *
     * @return array<string, TValue>
     */
    public function all(): array;

    /**
     * Is the set empty?
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Is the set not empty?
     *
     * @return bool
     */
    public function isNotEmpty(): bool;
}
