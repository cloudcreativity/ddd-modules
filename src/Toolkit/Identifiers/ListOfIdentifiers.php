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

use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Contracts\Toolkit\Iterables\ListIterator;
use CloudCreativity\Modules\Toolkit\Iterables\IsList;

/**
 * @implements ListIterator<Identifier>
 */
final class ListOfIdentifiers implements ListIterator
{
    /** @use IsList<Identifier> */
    use IsList;

    /**
     * ListOfIdentifiers constructor.
     *
     * @param Identifier ...$identifiers
     */
    public function __construct(Identifier ...$identifiers)
    {
        $this->stack = array_values($identifiers);
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
