<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Iterables;

use Generator;

/**
 * @template T
 */
trait KeyedSetTrait
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

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->stack);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->stack);
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !empty($this->stack);
    }
}
