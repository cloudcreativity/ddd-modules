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

use CloudCreativity\Modules\Tests\TestBackedEnum;
use CloudCreativity\Modules\Tests\TestUnitEnum;
use CloudCreativity\Modules\Toolkit\ContractException;
use CloudCreativity\Modules\Toolkit\Identifiers\Guid;
use CloudCreativity\Modules\Toolkit\Identifiers\LazyListOfGuids;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnitEnum;

class LazyListOfGuidsTest extends TestCase
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function typeProvider(): array
    {
        return [
            'string' => ['SomeType', 'SomeType', 'Other', 'Other'],
            'unit enum' => [
                TestUnitEnum::Baz,
                TestUnitEnum::Baz->name,
                TestUnitEnum::Bat,
                TestUnitEnum::Bat->name,
            ],
            'backed enum' => [
                TestBackedEnum::Foo,
                TestBackedEnum::Foo->value,
                TestBackedEnum::Bar,
                TestBackedEnum::Bar->value,
            ],
        ];
    }

    /**
     * @param UnitEnum|string $type
     * @return void
     */
    #[DataProvider('typeProvider')]
    public function testOfOneTypeDoesNotThrowWhenItHasTheExpectedType(UnitEnum|string $type): void
    {
        $expected = [
            Guid::fromInteger($type, 1),
            Guid::fromInteger($type, 2),
            Guid::fromInteger($type, 3),
        ];

        $guids = new LazyListOfGuids(function () use ($expected) {
            yield from $expected;
        });

        $actual = iterator_to_array($guids->ofOneType($type));

        $this->assertSame($expected, $actual);
    }

    /**
     * @param UnitEnum|string $type
     * @param string $value
     * @param UnitEnum|string $other
     * @param string $otherValue
     * @return void
     */
    #[DataProvider('typeProvider')]
    public function testOfOneTypeThrowsWhenItIsNot(
        UnitEnum|string $type,
        string $value,
        UnitEnum|string $other,
        string $otherValue,
    ): void {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage(sprintf(
            'Expecting GUIDs of type "%s", found "%s".',
            $value,
            $otherValue,
        ));

        $guids = new LazyListOfGuids(function () use ($type, $other) {
            yield Guid::fromInteger($type, 1);
            yield Guid::fromInteger($type, 2);
            yield Guid::fromInteger($other, 3);
        });

        iterator_to_array($guids->ofOneType($type));
    }

    public function testOnlyYieldsExpectedTypes(): void
    {
        $guid1 = Guid::fromInteger($expected1 = TestUnitEnum::Baz, 1);
        $guid2 = Guid::fromInteger(TestUnitEnum::Bat, 2);
        $guid3 = Guid::fromInteger($expected2 = TestBackedEnum::Foo, 3);
        $guid4 = Guid::fromInteger(TestBackedEnum::Bar, 4);
        $guid5 = Guid::fromInteger($expected3 = 'SomeType', 5);
        $guid6 = Guid::fromInteger('SomeOtherType', 5);
        $guid7 = Guid::fromInteger($expected1, 7);
        $guid8 = Guid::fromInteger($expected2, 7);
        $guid9 = Guid::fromInteger($expected3, 7);

        $all = [$guid1, $guid2, $guid3, $guid4, $guid5, $guid6, $guid7, $guid8, $guid9];
        $expected = [$guid1, $guid3, $guid5, $guid7, $guid8, $guid9];

        $guids = new LazyListOfGuids(function () use ($all) {
            yield from $all;
        });

        $actual = $guids->only($expected1, $expected3, $expected2)->all();

        $this->assertSame($expected, $actual);
    }
}
