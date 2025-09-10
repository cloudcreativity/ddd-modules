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
     */
    function enum_value(int|string|UnitEnum $value): int|string
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
     */
    function enum_string(string|UnitEnum $value): string
    {
        return match (true) {
            $value instanceof BackedEnum && is_string($value->value) => $value->value,
            $value instanceof UnitEnum => $value->name,
            default => $value,
        };
    }
}
