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

use Stringable;

interface IdentifierInterface extends Stringable
{
    /**
     * Is the identifier the same as the provided identifier?
     *
     * @param IdentifierInterface|null $other
     * @return bool
     */
    public function is(?self $other): bool;

    /**
     * Fluent to-string method.
     *
     * @return string
     */
    public function toString(): string;

    /**
     * Get the value for the identifier when it is being used as an array key.
     *
     * @return array-key
     */
    public function key(): string|int;

    /**
     * Get the value to use when adding the identifier to log context.
     *
     * @return mixed
     */
    public function context(): mixed;
}
