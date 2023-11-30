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
use CloudCreativity\Modules\Toolkit\Contracts;

/**
 * @template TValue
 * @implements ResultInterface<TValue>
 */
final class Result implements ResultInterface
{
    /**
     * @var Meta|null
     */
    private ?Meta $meta = null;

    /**
     * Return a success result.
     *
     * @template TSuccess
     * @param TSuccess $value
     * @return Result<TSuccess>
     */
    public static function ok(mixed $value = null): self
    {
        return new self(success: true, value: $value);
    }

    /**
     * Return a failed result.
     *
     * @param ListOfErrorsInterface|ErrorInterface|BackedEnum|array<ErrorInterface>|string $errorOrErrors
     * @return Result<null>
     */
    public static function failed(
        ListOfErrorsInterface|ErrorInterface|BackedEnum|array|string $errorOrErrors,
    ): self {
        $errors = match(true) {
            $errorOrErrors instanceof ListOfErrorsInterface => $errorOrErrors,
            default => ListOfErrors::from($errorOrErrors),
        };

        assert($errors->isNotEmpty(), 'Expecting at least one error message for a failed result.');

        return new self(success: false, errors: $errors);
    }

    /**
     * Result constructor.
     *
     * @param bool $success
     * @param TValue $value
     * @param ListOfErrorsInterface $errors
     */
    private function __construct(
        private readonly bool $success,
        private readonly mixed $value = null,
        private readonly ListOfErrorsInterface $errors = new ListOfErrors(),
    ) {
    }

    /**
     * @inheritDoc
     */
    public function didSucceed(): bool
    {
        return $this->success === true;
    }

    /**
     * @inheritDoc
     */
    public function didFail(): bool
    {
        return $this->success === false;
    }

    /**
     * @inheritDoc
     */
    public function value(): mixed
    {
        Contracts::assert($this->success, 'Result did not succeed.');

        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function safe(): mixed
    {
        return $this->success ? $this->value : null;
    }

    /**
     * @inheritDoc
     */
    public function errors(): ListOfErrorsInterface
    {
        return $this->errors;
    }

    /**
     * @inheritDoc
     */
    public function error(): ?string
    {
        return $this->errors->first()?->message();
    }

    /**
     * @inheritDoc
     */
    public function meta(): Meta
    {
        if ($this->meta) {
            return $this->meta;
        }

        return $this->meta = new Meta();
    }

    /**
     * @param Meta|array<string, mixed> $meta
     * @return Result<TValue>
     */
    public function withMeta(Meta|array $meta): self
    {
        $existing = $this->meta ?? new Meta();

        $copy = clone $this;
        $copy->meta = $existing->merge($meta);

        return $copy;
    }
}
