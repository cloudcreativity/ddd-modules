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

use Closure;
use Generator;

trait LazyIteratorTrait
{
    /**
     * @var Closure|null
     */
    private ?Closure $source = null;

    /**
     * @return Generator<mixed>
     */
    private function cursor(): Generator
    {
        if ($this->source === null) {
            return;
        }

        $iterator = ($this->source)();

        assert(is_iterable($iterator), 'Expecting source to yield an iterable.');

        yield from $iterator;
    }
}
