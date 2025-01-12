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

use Closure;
use CloudCreativity\Modules\Contracts\Toolkit\Iterables\LazyList;
use CloudCreativity\Modules\Toolkit\Contracts;
use CloudCreativity\Modules\Toolkit\Iterables\IsLazyList;
use Generator;

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
