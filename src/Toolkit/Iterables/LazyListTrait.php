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

use Closure;
use Generator;

/**
 * @template T
 */
trait LazyListTrait
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

        foreach(($this->source)() as $value) {
            yield $value;
        }
    }

    /**
     * @return array<T>
     */
    public function all(): array
    {
        return iterator_to_array($this->getIterator());
    }
}
