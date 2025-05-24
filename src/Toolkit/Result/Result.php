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
 * @template TValue
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
     * @param IListOfErrors|IError|UnitEnum|array<IError>|string $errorOrErrors
     * @return Result<null>
     */
    public static function failed(IListOfErrors|IError|UnitEnum|array|string $errorOrErrors): self
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
     * @param IListOfErrors|IError|UnitEnum|array<IError>|string $errorOrErrors
     * @return Result<null>
     */
    public static function fail(IListOfErrors|IError|UnitEnum|array|string $errorOrErrors): self
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
        private bool $success,
        private mixed $value = null,
        private IListOfErrors $errors = new ListOfErrors(),
        private Meta $meta = new Meta(),
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
        return new self(
            success: $this->success,
            value: $this->value,
            errors: $this->errors,
            meta: $this->meta->merge($meta),
        );
    }
}
