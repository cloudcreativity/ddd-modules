<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Iterables;

use CloudCreativity\Modules\Toolkit\Iterables\LazyListInterface;
use CloudCreativity\Modules\Toolkit\Iterables\LazyListTrait;
use PHPUnit\Framework\TestCase;

class LazyListTraitTest extends TestCase
{
    /**
     * @return void
     */
    public function testItIteratesOverList(): void
    {
        $expected = ['one', 'two', 'three'];

        /**
         * @implements LazyListInterface<string>
         */
        $list = new class (...$expected) implements LazyListInterface {
            /** @use LazyListTrait<string> */
            use LazyListTrait;

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
         * @implements LazyListInterface<string>
         */
        $list = new class ($expected) implements LazyListInterface {
            /** @use LazyListTrait<string> */
            use LazyListTrait;

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
