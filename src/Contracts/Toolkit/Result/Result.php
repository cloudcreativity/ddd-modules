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

use CloudCreativity\Modules\Contracts\Toolkit\Result\ListOfErrors as IListOfErrors;
use CloudCreativity\Modules\Toolkit\Result\FailedResultException;
use CloudCreativity\Modules\Toolkit\Result\Meta;

/**
 * @template-covariant TValue
 */
interface Result
{
    /**
     * @return bool
     */
    public function didSucceed(): bool;

    /**
     * @return bool
     */
    public function didFail(): bool;

    /**
     * Abort execution if the result failed.
     *
     * @return void
     * @throws FailedResultException if the result is not a success.
     */
    public function abort(): void;

    /**
     * Get the result value, if the result was successful.
     *
     * @return TValue
     * @throws FailedResultException if the result was not successful.
     */
    public function value(): mixed;

    /**
     * Get the result value, regardless of whether the result was successful.
     *
     * @return TValue|null
     */
    public function safe(): mixed;

    /**
     * Get the errors.
     *
     * @return IListOfErrors
     */
    public function errors(): IListOfErrors;

    /**
     * Get an error message string.
     *
     * @return string|null
     */
    public function error(): ?string;

    /**
     * Get the result meta.
     *
     * @return Meta
     */
    public function meta(): Meta;

    /**
     * Return a new instance with the provided meta.
     *
     * @param Meta|array<string, mixed> $meta
     * @return Result<TValue>
     */
    public function withMeta(Meta|array $meta): self;
}
