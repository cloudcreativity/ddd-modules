<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Identifiers;

use CloudCreativity\Modules\Toolkit\ContractException;
use CloudCreativity\Modules\Toolkit\Identifiers\Guid;
use CloudCreativity\Modules\Toolkit\Identifiers\LazyListOfGuids;
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
