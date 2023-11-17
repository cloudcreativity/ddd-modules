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

use CloudCreativity\BalancedEvent\Common\Infrastructure\Log\ContextProviderInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\ContractException;

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
