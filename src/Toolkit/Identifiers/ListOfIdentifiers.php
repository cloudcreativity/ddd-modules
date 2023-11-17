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

namespace CloudCreativity\Modules\Toolkit\Identifiers;

use CloudCreativity\Modules\Toolkit\Iterables\ListInterface;
use CloudCreativity\Modules\Toolkit\Iterables\ListTrait;

/**
 * @implements ListInterface<int, IdentifierInterface>
 */
class ListOfIdentifiers implements ListInterface
{
    use ListTrait;

    /**
     * @var array<int, IdentifierInterface>
     */
    private readonly array $stack;

    /**
     * ListOfIdentifiers constructor.
     *
     * @param IdentifierInterface ...$guids
     */
    public function __construct(IdentifierInterface ...$guids)
    {
        $this->stack = $guids;
    }

    /**
     * @return LazyListOfGuids
     */
    public function guids(): LazyListOfGuids
    {
        return new LazyListOfGuids(function () {
            yield from $this->stack;
        });
    }

    /**
     * @return LazyListOfIntegerIds
     */
    public function integerIds(): LazyListOfIntegerIds
    {
        return new LazyListOfIntegerIds(function () {
            yield from $this->stack;
        });
    }

    /**
     * @return LazyListOfStringIds
     */
    public function stringIds(): LazyListOfStringIds
    {
        return new LazyListOfStringIds(function () {
            yield from $this->stack;
        });
    }

    /**
     * @return LazyListOfUuids
     */
    public function uuids(): LazyListOfUuids
    {
        return new LazyListOfUuids(function () {
            yield from $this->stack;
        });
    }
}
