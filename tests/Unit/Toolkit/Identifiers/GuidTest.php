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
use CloudCreativity\Modules\Toolkit\Identifiers\IntegerId;
use CloudCreativity\Modules\Toolkit\Identifiers\StringId;
use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid as BaseUuid;
use UnitEnum;

class GuidTest extends TestCase
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
     * @param TestUnitEnum|string $type
     * @param string $value
     * @param TestUnitEnum|string $other
     * @return void
     * @dataProvider typeProvider
     */
    public function testStringId(UnitEnum|string $type, string $value, UnitEnum|string $other): void
    {
        $guid = Guid::fromString($type, '123');

        $this->assertInstanceOf(\Stringable::class, $guid);
        $this->assertSame($type, $guid->type);
        $this->assertSame($value, $guid->type());
        $this->assertObjectEquals(new StringId('123'), $guid->id);
        $this->assertSame($value . ':123', $guid->toString());
        $this->assertSame($value . ':123', (string) $guid);
        $this->assertTrue($guid->isType($type));
        $this->assertTrue($guid->is($guid));
        $this->assertTrue($guid->is(clone $guid));
        $this->assertFalse($guid->is(Guid::fromInteger($type, 123)));
        $this->assertFalse($guid->is(Guid::fromString($type, '234')));
        $this->assertFalse($guid->is(Guid::fromString($other, '123')));
        $this->assertFalse($guid->is(null));
        $this->assertSame(['type' => $value, 'id' => '123'], $guid->context());
        $this->assertEquals($guid, Guid::fromString($type, '123'));
        $this->assertObjectEquals($guid, Guid::fromString($type, '123'));
        $this->assertFalse($guid->equals(Guid::fromInteger($type, 123)));
    }

    /**
     * @param UnitEnum|string $type
     * @param string $value
     * @param UnitEnum|string $other
     * @return void
     * @dataProvider typeProvider
     */
    public function testIntegerId(UnitEnum|string $type, string $value, UnitEnum|string $other): void
    {
        $guid = Guid::fromInteger($type, 123);

        $this->assertSame($type, $guid->type);
        $this->assertObjectEquals(new IntegerId(123), $guid->id);
        $this->assertSame($value . ':123', $guid->toString());
        $this->assertSame($value . ':123', (string) $guid);
        $this->assertTrue($guid->isType($type));
        $this->assertTrue($guid->is($guid));
        $this->assertTrue($guid->is(clone $guid));
        $this->assertFalse($guid->is(Guid::fromString($type, '123')));
        $this->assertFalse($guid->is(Guid::fromInteger($type, 234)));
        $this->assertFalse($guid->is(Guid::fromInteger($other, 123)));
        $this->assertFalse($guid->is(null));
        $this->assertSame(['type' => $value, 'id' => 123], $guid->context());
        $this->assertEquals($guid, Guid::fromInteger($type, 123));
        $this->assertObjectEquals($guid, Guid::fromInteger($type, 123));
        $this->assertFalse($guid->equals(Guid::fromString($type, '123')));
    }

    /**
     * @param UnitEnum|string $type
     * @param string $value
     * @param UnitEnum|string $other
     * @return void
     * @dataProvider typeProvider
     */
    public function testUuid(UnitEnum|string $type, string $value, UnitEnum|string $other): void
    {
        $uuid = Uuid::random();
        $guid = Guid::fromUuid($type, $uuid->value);

        $this->assertSame($type, $guid->type);
        $this->assertObjectEquals($uuid, $guid->id);
        $this->assertSame($value . ':' . $uuid->toString(), $guid->toString());
        $this->assertSame($value . ':' . $uuid->toString(), (string) $guid);
        $this->assertTrue($guid->isType($type));
        $this->assertTrue($guid->is($guid));
        $this->assertTrue($guid->is(clone $guid));
        $this->assertFalse($guid->is(Guid::fromUuid($other, $uuid->value)));
        $this->assertFalse($guid->is(Guid::fromUuid($type, BaseUuid::uuid4())));
        $this->assertFalse($guid->is(Guid::fromString($type, $uuid->toString())));
        $this->assertFalse($guid->is(Guid::fromInteger($type, 234)));
        $this->assertFalse($guid->is(null));
        $this->assertSame(['type' => $value, 'id' => $uuid->toString()], $guid->context());
        $this->assertObjectEquals($guid, Guid::fromUuid($type, $uuid->value));
    }

    /**
     * @return void
     */
    public function testEmptyType(): void
    {
        $this->expectException(ContractException::class);
        Guid::fromString('', '123');
    }

    /**
     * @return void
     */
    public function testEmptyStringId(): void
    {
        $this->expectException(ContractException::class);
        Guid::fromString('SomeType', '');
    }

    /**
     * @return void
     */
    public function testNegativeIntegerId(): void
    {
        $this->expectException(ContractException::class);
        Guid::fromInteger('SomeType', -1);
    }

    /**
     * @return void
     */
    public function testFromInteger(): void
    {
        $guid = Guid::fromInteger('SomeType', 1);
        $this->assertObjectEquals(new IntegerId(1), $guid->id);
    }

    /**
     * @return void
     */
    public function testFromString(): void
    {
        $guid = Guid::fromString('SomeType', '1');
        $this->assertObjectEquals(new StringId('1'), $guid->id);
    }

    /**
     * @param UnitEnum|string $type
     * @return void
     * @dataProvider typeProvider
     */
    public function testAssertTypeDoesNotThrowForExpectedType(UnitEnum|string $type): void
    {
        $guid = Guid::fromInteger($type, 1);

        $actual = $guid->assertType($type);
        $this->assertSame($guid, $actual);
    }

    /**
     * @param UnitEnum|string $type
     * @param string $value
     * @param UnitEnum|string $other
     * @param string $otherValue
     * @return void
     * @dataProvider typeProvider
     */
    public function testAssertTypeDoesThrowForUnexpectedType(
        UnitEnum|string $type,
        string $value,
        UnitEnum|string $other,
        string $otherValue,
    ): void {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage(sprintf(
            'Expecting type "%s", received "%s".',
            $otherValue,
            $value,
        ));

        Guid::fromInteger($type, 1)->assertType($other);
    }
}
