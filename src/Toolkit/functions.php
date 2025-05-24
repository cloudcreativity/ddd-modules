<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit;

use BackedEnum;
use UnitEnum;

if (!function_exists(__NAMESPACE__ . '\enum_value')) {
    /**
     * Return a scalar value for an enum.
     *
     * @param UnitEnum|string|int $value
     * @return string|int
     */
    function enum_value(UnitEnum|string|int $value): string|int
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            default => $value,
        };
    }
}

if (!function_exists(__NAMESPACE__ . '\enum_string')) {
    /**
     * Return a string value for an enum.
     *
     * @param UnitEnum|string $value
     * @return string
     */
    function enum_string(UnitEnum|string $value): string
    {
        return match (true) {
            $value instanceof BackedEnum && is_string($value->value) => $value->value,
            $value instanceof UnitEnum => $value->name,
            default => $value,
        };
    }
}
