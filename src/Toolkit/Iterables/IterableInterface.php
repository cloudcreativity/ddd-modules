<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Toolkit\Iterables;

use Countable;
use IteratorAggregate;

/**
 * @template TKey
 * @template TValue
 * @extends IteratorAggregate<TKey,TValue>
 */
interface IterableInterface extends IteratorAggregate, Countable
{
    /**
     * Is the stack empty?
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Is the stack not empty?
     *
     * @return bool
     */
    public function isNotEmpty(): bool;
}
