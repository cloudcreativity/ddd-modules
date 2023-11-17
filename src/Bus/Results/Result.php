<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Bus\Results;

use CloudCreativity\BalancedEvent\Common\Toolkit\ContractException;
use InvalidArgumentException;

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
     * @param TValue|null $value
     * @return Result
     */
    public static function ok(mixed $value = null): self
    {
        return new self(success: true, value: $value);
    }

    /**
     * Return a failed result.
     *
     * @param ErrorIterableInterface|ErrorInterface|iterable|string $errorOrErrors
     * @return Result
     */
    public static function failed(ErrorIterableInterface|ErrorInterface|iterable|string $errorOrErrors): self
    {
        $errors = ErrorIterableFactory::getInstance()->make($errorOrErrors);

        if ($errors->isEmpty()) {
            throw new InvalidArgumentException('Expecting at least one error message for a failed result.');
        }

        return new self(success: false, errors: $errors);
    }

    /**
     * Result constructor.
     *
     * @param bool $success
     * @param TValue|null $value
     * @param ErrorIterableInterface $errors
     */
    private function __construct(
        private readonly bool $success,
        private readonly mixed $value = null,
        private readonly ErrorIterableInterface $errors = new ListOfErrors(),
    )
    {
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
        if ($this->success === true) {
            return $this->value;
        }

        throw new ContractException('Result did not succeed.');
    }

    /**
     * @inheritDoc
     */
    public function errors(): ErrorIterableInterface
    {
        return $this->errors;
    }

    /**
     * @inheritDoc
     */
    public function error(): ?string
    {
        $error = $this
            ->errors
            ?->toList()
            ?->first();

        return $error?->message();
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
     * @inheritDoc
     */
    public function withMeta(Meta|array $meta): self
    {
        $existing = $this->meta ?? new Meta();

        $copy = clone $this;
        $copy->meta = $existing->merge($meta);

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function context(): array
    {
        return array_filter([
            'errors' => $this->errors?->context() ?: null,
            'meta' => $this->meta?->context() ?: null,
            'success' => $this->success,
        ], static fn($value) => $value !== null);
    }
}
