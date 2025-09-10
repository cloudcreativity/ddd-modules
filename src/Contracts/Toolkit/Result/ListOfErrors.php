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

use Closure;
use CloudCreativity\Modules\Contracts\Toolkit\Iterables\ListIterator;
use UnitEnum;

/**
 * @extends ListIterator<Error>
 */
interface ListOfErrors extends ListIterator
{
    /**
     * Get the first error in the list, or the first matching error.
     *
     * @param Closure(Error): bool|UnitEnum|null $matcher
     */
    public function first(Closure|UnitEnum|null $matcher = null): ?Error;

    /**
     * Does the list contain a matching error?
     *
     * @param Closure(Error): bool|UnitEnum $matcher
     */
    public function contains(Closure|UnitEnum $matcher): bool;

    /**
     * Get all the unique error codes in the list.
     *
     * @return array<UnitEnum>
     */
    public function codes(): array;

    /**
     * Get the first error code in the list.
     *
     */
    public function code(): ?UnitEnum;

    /**
     * Return a new instance with the provided error pushed on to the end of the list.
     *
     * @return static
     */
    public function push(Error $error): self;

    /**
     * Return a new instance with the provided errors merged in.
     *
     * @return static
     */
    public function merge(ListOfErrors $other): self;
}
