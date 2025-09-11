<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit;

use CloudCreativity\Modules\Tests\TestBackedEnum;
use CloudCreativity\Modules\Tests\TestBackedIntEnum;
use CloudCreativity\Modules\Tests\TestUnitEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnitEnum;

use function CloudCreativity\Modules\Toolkit\enum_value;

class EnumValueTest extends TestCase
{
    /**
     * @return array<string, array{0: int|string|UnitEnum, 1: int|string}>
     */
    public static function valueProvider(): array
    {
        return [
            'string backed enum' => [
                TestBackedEnum::Foo,
                TestBackedEnum::Foo->value,
            ],
            'int backed enum' => [
                TestBackedIntEnum::BazBat,
                TestBackedIntEnum::BazBat->value,
            ],
            'unit enum' => [
                TestUnitEnum::Bat,
                TestUnitEnum::Bat->name,
            ],
            'string' => [
                'foo',
                'foo',
            ],
            'empty string' => [
                '',
                '',
            ],
            'int' => [
                123,
                123,
            ],
        ];
    }

    #[DataProvider('valueProvider')]
    public function testItReturnsScalarValue(int|string|UnitEnum $value, int|string $expected): void
    {
        $this->assertSame($expected, enum_value($value));
    }
}
