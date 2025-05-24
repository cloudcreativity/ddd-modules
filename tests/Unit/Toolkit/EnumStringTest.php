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

use function CloudCreativity\Modules\Toolkit\enum_string;

class EnumStringTest extends TestCase
{
    /**
     * @return array<string, array{0: UnitEnum|string|int, 1: string}>
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
                TestBackedIntEnum::BazBat->name,
            ],
            'unit enum' => [
                TestUnitEnum::Bat,
                TestUnitEnum::Bat->name,
            ],
            'string' => [
                'foo',
                'foo',
            ],
        ];
    }

    #[DataProvider('valueProvider')]
    public function testItReturnsScalarValue(UnitEnum|string $value, string $expected): void
    {
        $this->assertSame($expected, enum_string($value));
    }
}
