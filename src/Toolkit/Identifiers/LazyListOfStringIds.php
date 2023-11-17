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

use Closure;
use CloudCreativity\BalancedEvent\Common\Toolkit\Contracts;
use CloudCreativity\BalancedEvent\Common\Toolkit\Iterables\LazyIteratorTrait;
use Generator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, StringId>
 */
class LazyListOfStringIds implements IteratorAggregate
{
    use LazyIteratorTrait;

    /**
     * LazyListOfStringIds constructor.
     *
     * @param Closure|null $source
     */
    public function __construct(Closure $source = null)
    {
        $this->source = $source;
    }

    /**
     * @return Generator<int, StringId>
     */
    public function getIterator(): Generator
    {
        foreach ($this->cursor() as $id) {
            Contracts::assert($id instanceof StringId, 'Expecting identifiers to only contain string ids.');
            yield $id;
        }
    }

    /**
     * @return array<string>
     */
    public function toBase(): array
    {
        $ids = [];

        foreach ($this as $id) {
            $ids[] = $id->value;
        }

        return $ids;
    }
}