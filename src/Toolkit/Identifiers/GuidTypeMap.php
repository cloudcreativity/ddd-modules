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
     */
    public function define(string|UnitEnum $alias, string|UnitEnum $type): void
    {
        $alias = enum_string($alias);

        $this->map[$alias] = $type;
    }

    /**
     * Get the GUID for the specified alias and id.
     *
     */
    public function guidFor(string|UnitEnum $alias, int|string|Uuid|UuidInterface $id): Guid
    {
        return Guid::make($this->typeFor($alias), $id);
    }

    /**
     * Get the GUID type for the specified alias.
     *
     */
    public function typeFor(string|UnitEnum $alias): string|UnitEnum
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
