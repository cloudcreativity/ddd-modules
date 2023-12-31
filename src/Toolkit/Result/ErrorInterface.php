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

namespace CloudCreativity\Modules\Toolkit\Result;

use BackedEnum;

interface ErrorInterface
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
