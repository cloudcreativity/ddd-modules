<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers;

use Stringable;

interface IdentifierInterface extends Stringable
{
    /**
     * Is the identifier the same as the provided identifier?
     *
     * @param IdentifierInterface|null $other
     * @return bool
     */
    public function is(?self $other): bool;

    /**
     * Fluent to-string method.
     *
     * @return string
     */
    public function toString(): string;

    /**
     * Get the value for the identifier when it is being used as an array key.
     *
     * @return array-key
     */
    public function key(): string|int;

    /**
     * Get the value to use when adding the identifier to log context.
     *
     * @return mixed
     */
    public function context(): mixed;
}