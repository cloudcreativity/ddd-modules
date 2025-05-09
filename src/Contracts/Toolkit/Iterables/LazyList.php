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

use IteratorAggregate;

/**
 * @template TValue
 * @extends IteratorAggregate<TValue>
 */
interface LazyList extends IteratorAggregate
{
    /**
     * Eagerly load all values into an array.
     *
     * @return list<TValue>
     */
    public function all(): array;
}
