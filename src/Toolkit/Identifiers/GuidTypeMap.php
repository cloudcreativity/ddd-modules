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
