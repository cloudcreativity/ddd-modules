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

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Toolkit\Identifiers;

use CloudCreativity\BalancedEvent\Common\Toolkit\ContractException;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\Guid;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\LazyListOfGuids;
use PHPUnit\Framework\TestCase;

class LazyListOfGuidsTest extends TestCase
{
    /**
     * @return void
     * @throws \Exception
     */
    public function testOfOneTypeDoesNotThrowWhenItHasTheExpectedType(): void
    {
        $expected = [
            Guid::fromInteger('SomeType', 1),
            Guid::fromInteger('SomeType', 2),
            Guid::fromInteger('SomeType', 3),
        ];

        $guids = new LazyListOfGuids(function () use ($expected) {
            yield from $expected;
        });

        $actual = iterator_to_array($guids->ofOneType('SomeType'));

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testOfOneTypeThrowsWhenItIsNot(): void
    {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Expecting GUIDs of type "SomeType", found "SomeOtherType".');

        $guids = new LazyListOfGuids(function () {
            yield Guid::fromInteger('SomeType', 1);
            yield Guid::fromInteger('SomeType', 2);
            yield Guid::fromInteger('SomeOtherType', 3);
        });

        iterator_to_array($guids->ofOneType('SomeType'));
    }
}
