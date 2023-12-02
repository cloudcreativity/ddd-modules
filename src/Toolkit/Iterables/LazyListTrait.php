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
