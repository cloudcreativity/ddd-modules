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

use Generator;

trait ListTrait
{
    /**
     * @inheritDoc
     */
    public function getIterator(): Generator
    {
        yield from $this->stack;
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        if (is_array($this->stack)) {
            return $this->stack;
        }

        return iterator_to_array($this);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->stack);
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool
    {
        return empty($this->stack);
    }

    /**
     * @inheritDoc
     */
    public function isNotEmpty(): bool
    {
        return !empty($this->stack);
    }
}
