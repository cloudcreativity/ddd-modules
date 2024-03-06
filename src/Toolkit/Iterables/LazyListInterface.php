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

use IteratorAggregate;

/**
 * @template TValue
 * @extends IteratorAggregate<TValue>
 */
interface LazyListInterface extends IteratorAggregate
{
    /**
     * Eagerly load all values into an array.
     *
     * @return array<TValue>
     */
    public function all(): array;
}
