<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Identifiers;

use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Toolkit\ContractException;
use CloudCreativity\Modules\Toolkit\Identifiers\Guid;
use CloudCreativity\Modules\Toolkit\Identifiers\IntegerId;
use CloudCreativity\Modules\Toolkit\Identifiers\StringId;
use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use CloudCreativity\Modules\Toolkit\Identifiers\UuidV4;
use CloudCreativity\Modules\Toolkit\Identifiers\UuidV7;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid as RamseyUuid;

class UuidV4Test extends TestCase
{
    public function test(): void
    {
        $base = RamseyUuid::uuid4();
        $id = UuidV4::from($base);

        $this->assertSame($base, $id->value);
        $this->assertSame($base->toString(), $id->context());
        $this->assertSame($base->toString(), $id->key());
        $this->assertSame($base->toString(), $id->toString());
        $this->assertSame((string) $base, (string) $id);
        $this->assertSame($base->getBytes(), $id->getBytes());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['id' => $base], \JSON_THROW_ON_ERROR),
            json_encode(compact('id'), \JSON_THROW_ON_ERROR),
        );
    }

    public function testItIsEquals(): void
    {
        $base = RamseyUuid::uuid4();
        $id = UuidV4::from($base);

        $this->assertTrue($id->is($other = UuidV4::from($base)));
        $this->assertSame($id, UuidV4::from($id));
        $this->assertTrue($id->is($other));
        $this->assertTrue($id->any(null, UuidV4::make(), $other));
        $this->assertEquals($id, UuidV4::tryFrom($base));
        $this->assertSame($id, UuidV4::tryFrom($id));
        $this->assertTrue($id->is(new Uuid($base)));
    }

    public function testItIsNotEqual(): void
    {
        $id = UuidV4::from(RamseyUuid::fromString('6dcbad65-ed92-4e60-973b-9ba58a022816'));
        $this->assertFalse($id->is($other = UuidV4::from(
            RamseyUuid::fromString('38c7be26-6887-4742-8b6b-7d07b30ca596'),
        )));
        $this->assertFalse($id->is($other));
        $this->assertFalse($id->any(null, UuidV4::make(), $other));
    }

    /**
     * @return array<array{0: mixed}>
     */
    public static function notUuidV4Provider(): array
    {
        return [
            [null],
            [new IntegerId(1)],
            [new StringId('foo')],
            [new Guid('SomeType', new Uuid(RamseyUuid::fromString('6dcbad65-ed92-4e60-973b-9ba58a022816')))],
            [new Uuid(RamseyUuid::uuid7())],
            [UuidV7::make()],
        ];
    }

    #[DataProvider('notUuidV4Provider')]
    public function testIsWithOtherIdentifiers(?Identifier $other): void
    {
        $id = UuidV4::from(RamseyUuid::fromString('6dcbad65-ed92-4e60-973b-9ba58a022816'));

        $this->assertFalse($id->is($other));
        $this->assertFalse($id->any(null, UuidV4::make(), $other));
        $this->assertFalse($id->any());
    }

    #[DataProvider('notUuidV4Provider')]
    public function testFromWithOtherIdentifiers(?Identifier $other): void
    {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Unexpected identifier type, received: ' . get_debug_type($other));
        UuidV4::from($other);
    }

    #[DataProvider('notUuidV4Provider')]
    public function testTryFromWithOtherIdentifiers(?Identifier $other): void
    {
        $this->assertNull(UuidV4::tryFrom($other));
    }

    public function testFromWithString(): void
    {
        $base = RamseyUuid::fromString('6dcbad65-ed92-4e60-973b-9ba58a022816');

        $this->assertTrue(UuidV4::from($base)->is(UuidV4::from($base->toString())));
    }

    public function testTryFromWithString(): void
    {
        $base = RamseyUuid::fromString('6dcbad65-ed92-4e60-973b-9ba58a022816');

        $this->assertTrue(UuidV4::from($base)->is(UuidV4::tryFrom($base->toString())));
        $this->assertNull(UuidV4::tryFrom('invalid'));
    }

    public function testTryFromWithNull(): void
    {
        $this->assertNull(Uuid::tryFrom(null));
    }

    public function testCompareTo(): void
    {
        $uuid1 = UuidV4::make();
        $uuid2 = UuidV4::make();
        $uuid3 = UuidV4::make();

        $expected = [$uuid3->value, $uuid1->value, $uuid2->value];
        usort($expected, fn ($a, $b) => $a->compareTo($b));

        $actual = [$uuid2, $uuid1, $uuid3];
        usort($actual, fn ($a, $b) => $a->compareTo($b));
        $actual = array_map(fn ($id) => $id->value, $actual);

        $this->assertSame($expected, $actual);
    }
}
