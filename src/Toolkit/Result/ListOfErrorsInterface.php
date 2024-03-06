<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
     * Get all the unique error codes in the list.
     *
     * @return array<BackedEnum>
     */
    public function codes(): array;

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
