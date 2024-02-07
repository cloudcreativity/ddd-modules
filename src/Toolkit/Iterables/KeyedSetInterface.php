<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

use Countable;
use IteratorAggregate;

/**
 * @template TValue
 * @extends IteratorAggregate<string,TValue>
 */
interface KeyedSetInterface extends IteratorAggregate, Countable
{
    /**
     * Get the keyed set as an array.
     *
     * @return array<string, TValue>
     */
    public function all(): array;

    /**
     * Is the set empty?
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Is the set not empty?
     *
     * @return bool
     */
    public function isNotEmpty(): bool;
}
