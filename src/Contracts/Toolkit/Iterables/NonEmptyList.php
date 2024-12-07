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
 * @extends IteratorAggregate<TValue>
 */
interface NonEmptyList extends IteratorAggregate, Countable
{
    /**
     * Get the list as an array.
     *
     * @return non-empty-list<TValue>
     */
    public function all(): array;
}
