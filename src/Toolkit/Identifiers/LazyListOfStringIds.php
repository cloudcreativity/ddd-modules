<?php

/*
 * Copyright 2025 Cloud Creativity Limited
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
 * @implements LazyList<StringId>
 */
final class LazyListOfStringIds implements LazyList
{
    /** @use IsLazyList<StringId> */
    use IsLazyList;

    /**
     * LazyListOfStringIds constructor.
     *
     * @param Closure(): Generator<StringId>|null $source
     */
    public function __construct(?Closure $source = null)
    {
        $this->source = $source;
    }

    /**
     * @return array<string>
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
