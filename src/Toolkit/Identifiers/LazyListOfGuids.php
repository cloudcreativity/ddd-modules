<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Identifiers;

use Closure;
use CloudCreativity\Modules\Toolkit\Contracts;
use CloudCreativity\Modules\Toolkit\Iterables\LazyListInterface;
use CloudCreativity\Modules\Toolkit\Iterables\LazyListTrait;
use Generator;

/**
 * @implements LazyListInterface<Guid>
 */
final class LazyListOfGuids implements LazyListInterface
{
    /** @use LazyListTrait<Guid> */
    use LazyListTrait;

    /**
     * LazyListOfGuids constructor.
     *
     * @param Closure(): Generator<Guid>|null $source
     */
    public function __construct(Closure $source = null)
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
