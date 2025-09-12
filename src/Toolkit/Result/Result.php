<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Result;

use CloudCreativity\Modules\Contracts\Toolkit\Result\Error as IError;
use CloudCreativity\Modules\Contracts\Toolkit\Result\ListOfErrors as IListOfErrors;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result as IResult;
use UnitEnum;

/**
 * @template-covariant TValue
 * @implements IResult<TValue>
 */
final readonly class Result implements IResult
{
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
     * @param array<IError>|IError|IListOfErrors|string|UnitEnum $errorOrErrors
     * @return Result<null>
     */
    public static function failed(array|IError|IListOfErrors|string|UnitEnum $errorOrErrors): self
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
     * @param array<IError>|IError|IListOfErrors|string|UnitEnum $errorOrErrors
     * @return Result<null>
     */
    public static function fail(array|IError|IListOfErrors|string|UnitEnum $errorOrErrors): self
    {
        return self::failed($errorOrErrors);
    }

    /**
     * @param TValue $value
     */
    private function __construct(
        private bool $success,
        private mixed $value = null,
        private IListOfErrors $errors = new ListOfErrors(),
        private Meta $meta = new Meta(),
    ) {
    }

    public function didSucceed(): bool
    {
        return $this->success === true;
    }

    public function didFail(): bool
    {
        return $this->success === false;
    }

    public function abort(): void
    {
        if ($this->success === false) {
            throw new FailedResultException($this);
        }
    }

    public function value(): mixed
    {
        if ($this->success === true) {
            return $this->value;
        }

        throw new FailedResultException($this);
    }

    public function safe(): mixed
    {
        return $this->success ? $this->value : null;
    }

    public function errors(): IListOfErrors
    {
        return $this->errors;
    }

    public function error(): ?string
    {
        foreach ($this->errors as $error) {
            if ($message = $error->message()) {
                return $message;
            }
        }

        return null;
    }

    public function meta(): Meta
    {
        return $this->meta;
    }

    /**
     * @param array<string, mixed>|Meta $meta
     * @return Result<TValue>
     */
    public function withMeta(array|Meta $meta): self
    {
        return new self(
            success: $this->success,
            value: $this->value,
            errors: $this->errors,
            meta: $this->meta->merge($meta),
        );
    }
}
