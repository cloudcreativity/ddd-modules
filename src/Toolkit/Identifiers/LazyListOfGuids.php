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
 * @implements IteratorAggregate<int, Guid>
 */
class LazyListOfGuids implements IteratorAggregate
{
    use LazyIteratorTrait;

    /**
     * LazyListOfGuids constructor.
     *
     * @param Closure|null $source
     */
    public function __construct(Closure $source = null)
    {
        $this->source = $source;
    }

    /**
     * @return Generator<int, Guid>
     */
    public function getIterator(): Generator
    {
        foreach ($this->cursor() as $id) {
            Contracts::assert($id instanceof Guid, 'Expecting identifiers to only contain GUIDs.');
            yield $id;
        }
    }

    /**
     * Ensure all GUIDs are of the expected type.
     *
     * @param string $expected
     * @param string $message
     * @return self
     */
    public function ofOneType(string $expected, string $message = ''): self
    {
        return new self(function () use ($expected, $message) {
            foreach ($this as $guid) {
                Contracts::assert($guid->isType($expected), $message ?: sprintf(
                    'Expecting GUIDs of type "%s", found "%s".',
                    $expected,
                    $guid->type,
                ));
                yield $guid;
            }
        });
    }
}