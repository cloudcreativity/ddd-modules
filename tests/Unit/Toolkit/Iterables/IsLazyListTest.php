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

use CloudCreativity\Modules\Contracts\Toolkit\Iterables\LazyList;
use CloudCreativity\Modules\Toolkit\Iterables\IsLazyList;
use PHPUnit\Framework\TestCase;

class IsLazyListTest extends TestCase
{
    /**
     * @return void
     */
    public function testItIteratesOverList(): void
    {
        $expected = ['one', 'two', 'three'];

        /**
         * @implements LazyList<string>
         */
        $list = new class (...$expected) implements LazyList {
            /** @use IsLazyList<string> */
            use IsLazyList;

            public function __construct(string ...$values)
            {
                $this->source = function () use ($values) {
                    yield from $values;
                };
            }
        };

        $this->assertSame($expected, $list->all());
    }

    /**
     * @return void
     */
    public function testItYieldsListFromKeyedSet(): void
    {
        $expected = ['one' => 'foo', 'two' => 'bar', 'three' => 'baz'];

        /**
         * @implements LazyList<string>
         */
        $list = new class ($expected) implements LazyList {
            /** @use IsLazyList<string> */
            use IsLazyList;

            /**
             * @param array<string, string> $values
             */
            public function __construct(array $values)
            {
                $this->source = function () use ($values) {
                    yield from $values;
                };
            }
        };

        $this->assertSame(['foo', 'bar', 'baz'], $list->all());
    }
}
