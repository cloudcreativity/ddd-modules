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

use AssertionError;
use CloudCreativity\Modules\Tests\TestBackedEnum;
use CloudCreativity\Modules\Tests\TestBackedIntEnum;
use CloudCreativity\Modules\Tests\TestUnitEnum;
use CloudCreativity\Modules\Toolkit\Identifiers\Guid;
use CloudCreativity\Modules\Toolkit\Identifiers\GuidTypeMap;
use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class GuidTypeMapTest extends TestCase
{
    public function testItReturnsExpectedType(): GuidTypeMap
    {
        $map = new GuidTypeMap([
            'SomeTypeA' => 'SomeOtherTypeA',
            'SomeTypeB' => 'SomeOtherTypeB',
            'SomeTypeC' => 'SomeOtherTypeC',
            'NotString' => true,
            'EmptyString' => '',
        ]);

        $this->assertSame('SomeOtherTypeA', $map->typeFor('SomeTypeA'));
        $this->assertSame('SomeOtherTypeB', $map->typeFor('SomeTypeB'));
        $this->assertSame('SomeOtherTypeC', $map->typeFor('SomeTypeC'));

        return $map;
    }

    #[Depends('testItReturnsExpectedType')]
    public function testItReturnsExpectedGuid(GuidTypeMap $map): void
    {
        $this->assertEquals(
            Guid::fromInteger('SomeOtherTypeA', 99),
            $map->guidFor('SomeTypeA', 99),
        );

        $this->assertEquals(
            Guid::fromString('SomeOtherTypeC', 'foobar'),
            $map->guidFor('SomeTypeC', 'foobar'),
        );
    }

    #[Depends('testItReturnsExpectedType')]
    public function testItThrowsIfTypeIsNotValidString(GuidTypeMap $map): void
    {
        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('Expecting type for alias "NotString" to be a non-empty string.');

        $map->typeFor('NotString');
    }

    #[Depends('testItReturnsExpectedType')]
    public function testItThrowsIfTypeIsEmptyString(GuidTypeMap $map): void
    {
        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('Expecting type for alias "EmptyString" to be a non-empty string.');

        $map->typeFor('EmptyString');
    }

    #[Depends('testItReturnsExpectedType')]
    public function testItThrowsIfTypeIsNotDefined(GuidTypeMap $map): void
    {
        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('Alias "NotDefined" is not defined in the type map.');

        $map->typeFor('NotDefined');
    }

    public function testItReturnsExpectedTypeWithEnums(): void
    {
        $map = new GuidTypeMap();
        $map->define(TestBackedEnum::Foo, 'SomeFooType');
        $map->define(TestBackedEnum::Bar, TestBackedIntEnum::FooBar);
        $map->define(TestUnitEnum::Baz, 'SomeBazType');
        $map->define(TestUnitEnum::Bat, TestBackedIntEnum::BazBat);

        $id = Uuid::random();

        $this->assertSame('SomeFooType', $map->typeFor(TestBackedEnum::Foo));
        $this->assertObjectEquals(Guid::fromUuid('SomeFooType', $id), $map->guidFor(TestBackedEnum::Foo, $id));
        $this->assertSame(TestBackedIntEnum::FooBar, $map->typeFor(TestBackedEnum::Bar));
        $this->assertObjectEquals(Guid::fromUuid(TestBackedIntEnum::FooBar, $id), $map->guidFor(TestBackedEnum::Bar, $id));
        $this->assertSame('SomeBazType', $map->typeFor(TestUnitEnum::Baz));
        $this->assertObjectEquals(Guid::fromUuid('SomeBazType', $id), $map->guidFor(TestUnitEnum::Baz, $id));
        $this->assertSame(TestBackedIntEnum::BazBat, $map->typeFor(TestUnitEnum::Bat));
        $this->assertObjectEquals(Guid::fromUuid(TestBackedIntEnum::BazBat, $id), $map->guidFor(TestUnitEnum::Bat, $id));
    }
}
