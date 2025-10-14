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

use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Tests\TestBackedEnum;
use CloudCreativity\Modules\Tests\TestUnitEnum;
use CloudCreativity\Modules\Toolkit\ContractException;
use CloudCreativity\Modules\Toolkit\Identifiers\Guid;
use CloudCreativity\Modules\Toolkit\Identifiers\IntegerId;
use CloudCreativity\Modules\Toolkit\Identifiers\StringId;
use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid as BaseUuid;
use Ramsey\Uuid\UuidInterface;
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
     * @param string|TestUnitEnum $type
     * @param string|TestUnitEnum $other
     */
    #[DataProvider('typeProvider')]
    public function testStringId(string|UnitEnum $type, string $value, string|UnitEnum $other): void
    {
        $guid = Guid::fromString($type, '123');

        $others = [
            Guid::fromInteger($type, 123),
            Guid::fromString($type, '234'),
            Guid::fromString($other, '123'),
        ];

        $this->assertInstanceOf(\Stringable::class, $guid);
        $this->assertSame($type, $guid->type);
        $this->assertObjectEquals(new StringId('123'), $guid->id);
        $this->assertSame($value . ':123', $guid->toString());
        $this->assertSame($value . ':123', (string) $guid);
        $this->assertTrue($guid->isType($type));
        $this->assertTrue($guid->is($guid));
        $this->assertTrue($guid->is(clone $guid));
        $this->assertTrue($guid->any($others[0], $others[1], clone $guid));
        $this->assertFalse($guid->is($others[0]));
        $this->assertFalse($guid->is($others[1]));
        $this->assertFalse($guid->is($others[2]));
        $this->assertFalse($guid->is(null));
        $this->assertFalse($guid->any(...$others));
        $this->assertFalse($guid->any(null, ...$others));
        $this->assertSame(['type' => $value, 'id' => '123'], $guid->context());
        $this->assertEquals($guid, Guid::fromString($type, '123'));
        $this->assertObjectEquals($guid, Guid::fromString($type, '123'));
        $this->assertFalse($guid->equals(Guid::fromInteger($type, 123)));
    }

    #[DataProvider('typeProvider')]
    public function testIntegerId(string|UnitEnum $type, string $value, string|UnitEnum $other): void
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

    #[DataProvider('typeProvider')]
    public function testUuid(string|UnitEnum $type, string $value, string|UnitEnum $other): void
    {
        $uuid = Uuid::random();
        $guid = Guid::fromUuid($type, $uuid->value);

        $this->assertSame($type, $guid->type);
        $this->assertObjectEquals($uuid, $guid->id);
        $this->assertObjectEquals($guid, Guid::fromUuid($type, $uuid));
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
     * @return array<string, array{0: int|string|Uuid|UuidInterface, 1: Identifier}>
     */
    public static function makeProvider(): array
    {
        $uuid = Uuid::random();

        return [
            'integer' => [123, new IntegerId(123)],
            'string' => ['some-slug', new StringId('some-slug')],
            'ramsey uuid' => [$uuid->value, $uuid],
            'uuid object' => [$uuid, $uuid],
            'string uuid' => [$uuid->toString(), $uuid],
        ];
    }

    #[DataProvider('makeProvider')]
    public function testItMakesGuid(int|string|Uuid|UuidInterface $value, Identifier $expected): void
    {
        $guid = Guid::make('SomeType', $value);
        $this->assertObjectEquals($expected, $guid->id);
        $this->assertSame($guid, Guid::from($guid));
        $this->assertSame($guid, Guid::tryFrom($guid));
    }

    public function testFromWithNull(): void
    {
        $this->assertNull(Guid::tryFrom(null));

        $this->expectException(ContractException::class);
        Guid::from(null);
    }

    public function testEmptyType(): void
    {
        $this->expectException(ContractException::class);
        Guid::fromString('', '123');
    }

    public function testEmptyStringId(): void
    {
        $this->expectException(ContractException::class);
        Guid::fromString('SomeType', '');
    }

    public function testNegativeIntegerId(): void
    {
        $this->expectException(ContractException::class);
        Guid::fromInteger('SomeType', -1);
    }

    public function testFromInteger(): void
    {
        $guid = Guid::fromInteger('SomeType', 1);
        $this->assertObjectEquals(new IntegerId(1), $guid->id);
    }

    public function testFromString(): void
    {
        $guid = Guid::fromString('SomeType', '1');
        $this->assertObjectEquals(new StringId('1'), $guid->id);
    }

    #[DataProvider('typeProvider')]
    public function testIsTypeWithMultipleTypes(string|UnitEnum $type, string $value, string|UnitEnum $other): void
    {
        $guid = Guid::fromInteger($type, 1);

        $this->assertTrue($guid->isType($other, $type));
        $this->assertFalse($guid->isType($other, 'Blah!'));
        $this->assertFalse($guid->isType());
    }

    #[DataProvider('typeProvider')]
    public function testAssertTypeDoesNotThrowForExpectedType(string|UnitEnum $type): void
    {
        $guid = Guid::fromInteger($type, 1);

        $actual = $guid->assertType($type);
        $this->assertSame($guid, $actual);
    }

    #[DataProvider('typeProvider')]
    public function testAssertTypeDoesThrowForUnexpectedType(
        string|UnitEnum $type,
        string $value,
        string|UnitEnum $other,
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
