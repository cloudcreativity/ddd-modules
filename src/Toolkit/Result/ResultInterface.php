<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

use CloudCreativity\Modules\Toolkit\ContractException;

/**
 * @template-covariant TValue
 */
interface ResultInterface
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
     * Get the result value, if the result was successful.
     *
     * @return TValue
     * @throws ContractException if the result was not successful.
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
     * @return ListOfErrorsInterface
     */
    public function errors(): ListOfErrorsInterface;

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
     * @return ResultInterface<TValue>
     */
    public function withMeta(Meta|array $meta): self;
}
