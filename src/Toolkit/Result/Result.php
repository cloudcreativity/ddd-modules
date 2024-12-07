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
use CloudCreativity\Modules\Contracts\Toolkit\Result\Error as IError;
use CloudCreativity\Modules\Contracts\Toolkit\Result\ListOfErrors as IListOfErrors;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result as IResult;

/**
 * @template TValue
 * @implements IResult<TValue>
 */
final class Result implements IResult
{
    /**
     * @var Meta
     */
    private Meta $meta;

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
     * @param IListOfErrors|IError|BackedEnum|array<IError>|string $errorOrErrors
     * @return Result<null>
     */
    public static function failed(IListOfErrors|IError|BackedEnum|array|string $errorOrErrors): self
    {
        $errors = match(true) {
            $errorOrErrors instanceof IListOfErrors => $errorOrErrors,
            default => ListOfErrors::from($errorOrErrors),
        };

        assert($errors->isNotEmpty(), 'Expecting at least one error message for a failed result.');

        return new self(success: false, errors: $errors);
    }

    /**
     * Return a failed result.
     *
     * This is an alias for the `failed` method.
     *
     * @param IListOfErrors|IError|BackedEnum|array<IError>|string $errorOrErrors
     * @return Result<null>
     */
    public static function fail(IListOfErrors|IError|BackedEnum|array|string $errorOrErrors): self
    {
        return self::failed($errorOrErrors);
    }

    /**
     * Result constructor.
     *
     * @param bool $success
     * @param TValue $value
     * @param IListOfErrors $errors
     */
    private function __construct(
        private readonly bool $success,
        private readonly mixed $value = null,
        private readonly IListOfErrors $errors = new ListOfErrors(),
    ) {
        $this->meta = new Meta();
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
    public function abort(): void
    {
        if ($this->success === false) {
            throw new FailedResultException($this);
        }
    }

    /**
     * @inheritDoc
     */
    public function value(): mixed
    {
        if ($this->success === true) {
            return $this->value;
        }

        throw new FailedResultException($this);
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
    public function errors(): IListOfErrors
    {
        return $this->errors;
    }

    /**
     * @inheritDoc
     */
    public function error(): ?string
    {
        foreach ($this->errors as $error) {
            if ($message = $error->message()) {
                return $message;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function meta(): Meta
    {
        return $this->meta;
    }

    /**
     * @param Meta|array<string, mixed> $meta
     * @return Result<TValue>
     */
    public function withMeta(Meta|array $meta): self
    {
        $copy = clone $this;
        $copy->meta = $this->meta->merge($meta);

        return $copy;
    }
}
