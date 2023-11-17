<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers;

readonly class GuidTypeMap
{
    /**
     * GuidTypeMap constructor
     *
     * @param array<string,string> $map
     */
    public function __construct(private array $map)
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