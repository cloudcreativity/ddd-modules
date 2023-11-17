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
use CloudCreativity\BalancedEvent\Common\Toolkit\Iterables\IterableInterface;

interface ErrorIterableInterface extends IterableInterface, ContextProviderInterface
{
    /**
     * Return a new instance with the provided errors merged in.
     *
     * @param ErrorIterableInterface $other
     * @return ErrorIterableInterface
     */
    public function merge(ErrorIterableInterface $other): self;

    /**
     * @return ListOfErrors
     */
    public function toList(): ListOfErrors;

    /**
     * @return KeyedSetOfErrors
     */
    public function toKeyedSet(): KeyedSetOfErrors;

    /**
     * @return ErrorInterface[]
     */
    public function all(): array;
}
