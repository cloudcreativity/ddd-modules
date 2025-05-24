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

use Ramsey\Uuid\UuidInterface;
use UnitEnum;

use function CloudCreativity\Modules\Toolkit\enum_string;

final class GuidTypeMap
{
    /**
     * GuidTypeMap constructor
     *
     * @param array<string, mixed> $map
     */
    public function __construct(private array $map = [])
    {
    }

    /**
     * Define an alias to type mapping.
     *
     * @param UnitEnum|string $alias
     * @param UnitEnum|string $type
     * @return void
     */
    public function define(UnitEnum|string $alias, UnitEnum|string $type): void
    {
        $alias = enum_string($alias);

        $this->map[$alias] = $type;
    }

    /**
     * Get the GUID for the specified alias and id.
     *
     * @param UnitEnum|string $alias
     * @param Uuid|UuidInterface|string|int $id
     * @return Guid
     */
    public function guidFor(UnitEnum|string $alias, Uuid|UuidInterface|string|int $id): Guid
    {
        return Guid::make($this->typeFor($alias), $id);
    }

    /**
     * Get the GUID type for the specified alias.
     *
     * @param UnitEnum|string $alias
     * @return UnitEnum|string
     */
    public function typeFor(UnitEnum|string $alias): UnitEnum|string
    {
        $alias = enum_string($alias);

        assert(
            isset($this->map[$alias]),
            sprintf('Alias "%s" is not defined in the type map.', $alias),
        );

        $type = $this->map[$alias] ?? null;

        assert(
            (is_string($type) || $type instanceof UnitEnum) && !empty($type),
            sprintf('Expecting type for alias "%s" to be a non-empty string.', $alias),
        );

        return $type;
    }
}
