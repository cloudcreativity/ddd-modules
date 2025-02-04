<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Identifiers;

use BackedEnum;
use Closure;
use CloudCreativity\Modules\Contracts\Toolkit\Iterables\LazyList;
use CloudCreativity\Modules\Toolkit\Contracts;
use CloudCreativity\Modules\Toolkit\Iterables\IsLazyList;
use Generator;
use UnitEnum;

/**
 * @implements LazyList<Guid>
 */
final class LazyListOfGuids implements LazyList
{
    /** @use IsLazyList<Guid> */
    use IsLazyList;

    /**
     * LazyListOfGuids constructor.
     *
     * @param Closure(): Generator<Guid>|null $source
     */
    public function __construct(?Closure $source = null)
    {
        $this->source = $source;
    }

    /**
     * Ensure all GUIDs are of the expected type.
     *
     * @param UnitEnum|string $expected
     * @param string $message
     * @return self
     */
    public function ofOneType(UnitEnum|string $expected, string $message = ''): self
    {
        return new self(function () use ($expected, $message) {
            foreach ($this as $guid) {
                Contracts::assert($guid->isType($expected), $message ?: sprintf(
                    'Expecting GUIDs of type "%s", found "%s".',
                    match (true) {
                        $expected instanceof BackedEnum => $expected->value,
                        $expected instanceof UnitEnum => $expected->name,
                        default => $expected,
                    },
                    $guid->type(),
                ));
                yield $guid;
            }
        });
    }

    /**
     * Return a list that only contains the provided types.
     *
     * @param UnitEnum|string ...$types
     * @return self
     */
    public function only(UnitEnum|string ...$types): self
    {
        return new self(function () use ($types) {
            foreach ($this as $guid) {
                if ($guid->isType(...$types)) {
                    yield $guid;
                }
            }
        });
    }
}
