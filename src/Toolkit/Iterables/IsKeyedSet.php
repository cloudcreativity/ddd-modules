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

use Generator;

/**
 * @template T
 */
trait IsKeyedSet
{
    /**
     * @var array<string, T>
     */
    private array $stack = [];

    /**
     * @return Generator<string, T>
     */
    public function getIterator(): Generator
    {
        yield from $this->stack;
    }

    /**
     * @return array<string, T>
     */
    public function all(): array
    {
        return $this->stack;
    }

    public function count(): int
    {
        return count($this->stack);
    }

    public function isEmpty(): bool
    {
        return empty($this->stack);
    }

    public function isNotEmpty(): bool
    {
        return !empty($this->stack);
    }
}
