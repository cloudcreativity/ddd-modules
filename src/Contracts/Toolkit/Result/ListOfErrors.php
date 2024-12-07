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
use Closure;
use CloudCreativity\Modules\Contracts\Toolkit\Iterables\ListIterator;

/**
 * @extends ListIterator<Error>
 */
interface ListOfErrors extends ListIterator
{
    /**
     * Get the first error in the list, or the first matching error.
     *
     * @param Closure(Error): bool|BackedEnum|null $matcher
     * @return Error|null
     */
    public function first(Closure|BackedEnum|null $matcher = null): ?Error;

    /**
     * Does the list contain a matching error?
     *
     * @param Closure(Error): bool|BackedEnum $matcher
     * @return bool
     */
    public function contains(Closure|BackedEnum $matcher): bool;

    /**
     * Get all the unique error codes in the list.
     *
     * @return array<BackedEnum>
     */
    public function codes(): array;

    /**
     * Return a new instance with the provided error pushed on to the end of the list.
     *
     * @param Error $error
     * @return static
     */
    public function push(Error $error): self;

    /**
     * Return a new instance with the provided errors merged in.
     *
     * @param ListOfErrors $other
     * @return static
     */
    public function merge(ListOfErrors $other): self;
}
