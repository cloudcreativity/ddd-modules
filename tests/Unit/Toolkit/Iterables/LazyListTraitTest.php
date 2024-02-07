<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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
