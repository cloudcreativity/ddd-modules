<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Identifiers;

use Closure;
use CloudCreativity\Modules\Contracts\Toolkit\Iterables\LazyList;
use CloudCreativity\Modules\Toolkit\Iterables\IsLazyList;
use Generator;
use Ramsey\Uuid\UuidInterface;

/**
 * @implements LazyList<Uuid>
 */
final class LazyListOfUuids implements LazyList
{
    /** @use IsLazyList<Uuid> */
    use IsLazyList;

    /**
     * LazyListOfUuids constructor.
     *
     * @param Closure(): Generator<Uuid>|null $source
     */
    public function __construct(?Closure $source = null)
    {
        $this->source = $source;
    }

    /**
     * @return array<UuidInterface>
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
