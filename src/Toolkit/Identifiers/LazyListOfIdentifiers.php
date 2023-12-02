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

use Closure;
use CloudCreativity\Modules\Toolkit\Iterables\LazyListTrait;
use Generator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<IdentifierInterface>
 */
final class LazyListOfIdentifiers implements IteratorAggregate
{
    /** @use LazyListTrait<IdentifierInterface> */
    use LazyListTrait;

    /**
     * LazyListOfIdentifiers constructor.
     *
     * @param Closure(): Generator<IdentifierInterface>|null $source
     */
    public function __construct(Closure $source = null)
    {
        $this->source = $source;
    }

    /**
     * @return LazyListOfGuids
     */
    public function guids(): LazyListOfGuids
    {
        return new LazyListOfGuids(function () {
            foreach ($this as $identifier) {
                yield Guid::from($identifier);
            }
        });
    }

    /**
     * @return LazyListOfIntegerIds
     */
    public function integerIds(): LazyListOfIntegerIds
    {
        return new LazyListOfIntegerIds(function () {
            foreach ($this as $identifier) {
                yield IntegerId::from($identifier);
            }
        });
    }

    /**
     * @return LazyListOfStringIds
     */
    public function stringIds(): LazyListOfStringIds
    {
        return new LazyListOfStringIds(function () {
            foreach ($this as $identifier) {
                yield StringId::from($identifier);
            }
        });
    }

    /**
     * @return LazyListOfUuids
     */
    public function uuids(): LazyListOfUuids
    {
        return new LazyListOfUuids(function () {
            foreach ($this as $identifier) {
                yield Uuid::from($identifier);
            }
        });
    }
}
