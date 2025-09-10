<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Iterables;

use CloudCreativity\Modules\Contracts\Toolkit\Iterables\NonEmptyList;
use CloudCreativity\Modules\Toolkit\Iterables\IsNonEmptyList;
use PHPUnit\Framework\TestCase;

class IsNonEmptyListTest extends TestCase
{
    public function test(): void
    {
        $expected = ['one', 'two', 'three'];

        /**
         * @implements NonEmptyList<string>
         */
        $list = new class (...$expected) implements NonEmptyList {
            /** @use IsNonEmptyList<string> */
            use IsNonEmptyList;

            public function __construct(string $value, string ...$values)
            {
                $this->stack = [$value, ...array_values($values)];
            }
        };

        $this->assertSame($expected, iterator_to_array($list));
        $this->assertSame($expected, $list->all());
        $this->assertCount(count($expected), $list);
    }
}
