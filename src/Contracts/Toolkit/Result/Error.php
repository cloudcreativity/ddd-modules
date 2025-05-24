<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Toolkit\Result;

use UnitEnum;

interface Error
{
    /**
     * Get the error key.
     *
     * @return string|null
     */
    public function key(): ?string;

    /**
     * Get the error detail.
     *
     * @return string
     */
    public function message(): string;

    /**
     * Get the error code.
     *
     * @return UnitEnum|null
     */
    public function code(): ?UnitEnum;

    /**
     * Is the error the specified error code?
     *
     * @param UnitEnum $code
     * @return bool
     */
    public function is(UnitEnum $code): bool;
}
