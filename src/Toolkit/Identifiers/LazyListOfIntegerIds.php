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

use Closure;
use CloudCreativity\Modules\Contracts\Toolkit\Iterables\LazyList;
use CloudCreativity\Modules\Toolkit\Iterables\IsLazyList;
use Generator;

/**
 * @implements LazyList<IntegerId>
 */
final class LazyListOfIntegerIds implements LazyList
{
    /** @use IsLazyList<IntegerId> */
    use IsLazyList;

    /**
     * LazyListOfIntegerIds constructor.
     *
     * @param Closure(): Generator<IntegerId>|null $source
     */
    public function __construct(?Closure $source = null)
    {
        $this->source = $source;
    }

    /**
     * @return array<int>
     */
    public function toBase(): array
    {
        $ids = [];

        foreach ($this as $id) {
            $ids[] = $id->value;
        }

        return $ids;
    }
}
