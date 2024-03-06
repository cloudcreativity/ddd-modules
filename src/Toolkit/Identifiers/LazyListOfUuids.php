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
use CloudCreativity\Modules\Toolkit\Iterables\LazyListInterface;
use CloudCreativity\Modules\Toolkit\Iterables\LazyListTrait;
use Generator;
use Ramsey\Uuid\UuidInterface;

/**
 * @implements LazyListInterface<Uuid>
 */
final class LazyListOfUuids implements LazyListInterface
{
    /** @use LazyListTrait<Uuid> */
    use LazyListTrait;

    /**
     * LazyListOfUuids constructor.
     *
     * @param Closure(): Generator<Uuid>|null $source
     */
    public function __construct(Closure $source = null)
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
