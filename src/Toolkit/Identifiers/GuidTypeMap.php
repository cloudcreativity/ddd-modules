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

final class GuidTypeMap
{
    /**
     * GuidTypeMap constructor
     *
     * @param array<string, mixed> $map
     */
    public function __construct(private readonly array $map)
    {
    }

    /**
     * Get the GUID for the specified alias and id.
     *
     * @param string $alias
     * @param string|int $id
     * @return Guid
     */
    public function guidFor(string $alias, string|int $id): Guid
    {
        return Guid::make($this->typeFor($alias), $id);
    }

    /**
     * Get the GUID type for the specified alias.
     *
     * @param string $alias
     * @return string
     */
    public function typeFor(string $alias): string
    {
        assert(
            isset($this->map[$alias]),
            sprintf('Alias "%s" is not defined in the type map.', $alias),
        );

        $type = $this->map[$alias] ?? null;

        assert(
            is_string($type) && !empty($type),
            sprintf('Expecting type for alias "%s" to be a non-empty string.', $alias),
        );

        return $type;
    }
}
