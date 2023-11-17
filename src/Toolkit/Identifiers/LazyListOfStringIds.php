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

namespace CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers;

use Closure;
use CloudCreativity\BalancedEvent\Common\Toolkit\Contracts;
use CloudCreativity\BalancedEvent\Common\Toolkit\Iterables\LazyIteratorTrait;
use Generator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, StringId>
 */
class LazyListOfStringIds implements IteratorAggregate
{
    use LazyIteratorTrait;

    /**
     * LazyListOfStringIds constructor.
     *
     * @param Closure|null $source
     */
    public function __construct(Closure $source = null)
    {
        $this->source = $source;
    }

    /**
     * @return Generator<int, StringId>
     */
    public function getIterator(): Generator
    {
        foreach ($this->cursor() as $id) {
            Contracts::assert($id instanceof StringId, 'Expecting identifiers to only contain string ids.');
            yield $id;
        }
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
