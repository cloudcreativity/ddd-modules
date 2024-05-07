<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Toolkit\Result;

use BackedEnum;

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
     * @return BackedEnum|null
     */
    public function code(): ?BackedEnum;

    /**
     * Is the error the specified error code?
     *
     * @param BackedEnum $code
     * @return bool
     */
    public function is(BackedEnum $code): bool;
}
