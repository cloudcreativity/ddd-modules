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
use Closure;
use CloudCreativity\Modules\Toolkit\Iterables\ListInterface;

/**
 * @extends ListInterface<ErrorInterface>
 */
interface ListOfErrorsInterface extends ListInterface
{
    /**
     * Get the first error in the list, or the first matching error.
     *
     * @param Closure(ErrorInterface): bool|BackedEnum|null $matcher
     * @return ErrorInterface|null
     */
    public function first(Closure|BackedEnum|null $matcher = null): ?ErrorInterface;

    /**
     * Does the list contain a matching error?
     *
     * @param Closure(ErrorInterface): bool|BackedEnum $matcher
     * @return bool
     */
    public function contains(Closure|BackedEnum $matcher): bool;

    /**
     * Return a new instance with the provided error pushed on to the end of the list.
     *
     * @param ErrorInterface $error
     * @return static
     */
    public function push(ErrorInterface $error): self;

    /**
     * Return a new instance with the provided errors merged in.
     *
     * @param ListOfErrorsInterface $other
     * @return static
     */
    public function merge(ListOfErrorsInterface $other): self;
}
