<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Iterables;

use Closure;
use Generator;

/**
 * @template T
 */
trait IsLazyList
{
    /**
     * @var Closure(): Generator<T>|null
     */
    private ?Closure $source = null;

    /**
     * @return Generator<T>
     */
    public function getIterator(): Generator
    {
        if ($this->source === null) {
            return;
        }

        foreach (($this->source)() as $value) {
            yield $value;
        }
    }

    /**
     * @return list<T>
     */
    public function all(): array
    {
        $all = [];

        foreach ($this as $value) {
            $all[] = $value;
        }

        return $all;
    }
}
