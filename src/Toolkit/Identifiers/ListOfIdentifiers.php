<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Identifiers;

use CloudCreativity\Modules\Toolkit\Iterables\ListInterface;
use CloudCreativity\Modules\Toolkit\Iterables\ListTrait;

/**
 * @implements ListInterface<IdentifierInterface>
 */
final class ListOfIdentifiers implements ListInterface
{
    /** @use ListTrait<IdentifierInterface> */
    use ListTrait;

    /**
     * ListOfIdentifiers constructor.
     *
     * @param IdentifierInterface ...$identifiers
     */
    public function __construct(IdentifierInterface ...$identifiers)
    {
        $this->stack = $identifiers;
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
