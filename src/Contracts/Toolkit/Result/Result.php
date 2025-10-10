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
     * Is the result a success?
     */
    public function didSucceed(): bool;

    /**
     * Is the result a failure?
     */
    public function didFail(): bool;

    /**
     * Abort execution if the result failed.
     *
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
     */
    public function errors(): IListOfErrors;

    /**
     * Get an error message string.
     */
    public function error(): ?string;

    /**
     * Get the result meta.
     */
    public function meta(): Meta;

    /**
     * Return a new instance with the provided meta.
     *
     * @param array<string, mixed>|Meta $meta
     * @return Result<TValue>
     */
    public function withMeta(array|Meta $meta): self;
}
