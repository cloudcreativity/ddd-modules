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

namespace CloudCreativity\Modules\Bus\Results;

use CloudCreativity\Modules\Infrastructure\Log\ContextProviderInterface;
use CloudCreativity\Modules\Toolkit\ContractException;

/**
 * @template TValue
 */
interface ResultInterface extends ContextProviderInterface
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
     * @return TValue
     * @throws ContractException if the result was not successful.
     */
    public function value(): mixed;

    /**
     * Get the errors.
     *
     * @return ErrorIterableInterface
     */
    public function errors(): ErrorIterableInterface;

    /**
     * Get a error message string.
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
     * @param Meta|array $meta
     * @return ResultInterface<TValue>
     */
    public function withMeta(Meta|array $meta): self;
}
