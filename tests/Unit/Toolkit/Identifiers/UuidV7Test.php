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

final class UuidV7Test extends TestCase
{
    public function test(): void
    {
        $base = RamseyUuid::uuid7();
        $id = UuidV7::from($base);

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
        $base = RamseyUuid::uuid7();
        $id = UuidV7::from($base);

        $this->assertTrue($id->is($other = UuidV7::from($base)));
        $this->assertSame($id, UuidV7::from($id));
        $this->assertTrue($id->is($other));
        $this->assertTrue($id->any(null, UuidV7::make(), $other));
        $this->assertEquals($id, UuidV7::tryFrom($base));
        $this->assertSame($id, UuidV7::tryFrom($id));
        $this->assertTrue($id->is(new Uuid($base)));
    }

    public function testItIsNotEqual(): void
    {
        $id = UuidV7::from(RamseyUuid::fromString('019b7acc-aff8-7f70-adc9-e9c7f632e6df'));
        $this->assertFalse($id->is($other = UuidV7::from(
            RamseyUuid::fromString('019b7acd-0f6f-7828-a1cf-94c34a239594'),
        )));
        $this->assertFalse($id->is($other));
        $this->assertFalse($id->any(null, UuidV7::make(), $other));
    }

    /**
     * @return array<array{0: mixed}>
     */
    public static function notUuidV7Provider(): array
    {
        return [
            [null],
            [new IntegerId(1)],
            [new StringId('foo')],
            [new Guid('SomeType', new Uuid(RamseyUuid::fromString('6dcbad65-ed92-4e60-973b-9ba58a022816')))],
            [Uuid::random()],
            [UuidV4::make()],
        ];
    }

    #[DataProvider('notUuidV7Provider')]
    public function testIsWithOtherIdentifiers(?Identifier $other): void
    {
        $id = UuidV7::from(RamseyUuid::fromString('019b7acc-aff8-7f70-adc9-e9c7f632e6df'));

        $this->assertFalse($id->is($other));
        $this->assertFalse($id->any(null, UuidV7::make(), $other));
        $this->assertFalse($id->any());
    }

    #[DataProvider('notUuidV7Provider')]
    public function testFromWithOtherIdentifiers(?Identifier $other): void
    {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Unexpected identifier type, received: ' . get_debug_type($other));
        UuidV7::from($other);
    }

    #[DataProvider('notUuidV7Provider')]
    public function testTryFromWithOtherIdentifiers(?Identifier $other): void
    {
        $this->assertNull(UuidV7::tryFrom($other));
    }

    public function testFromWithString(): void
    {
        $base = RamseyUuid::fromString('019b7acc-aff8-7f70-adc9-e9c7f632e6df');

        $this->assertTrue(UuidV7::from($base)->is(UuidV7::from($base->toString())));
    }

    public function testTryFromWithString(): void
    {
        $base = RamseyUuid::fromString('019b7acc-aff8-7f70-adc9-e9c7f632e6df');

        $this->assertTrue(UuidV7::from($base)->is(UuidV7::tryFrom($base->toString())));
        $this->assertNull(UuidV7::tryFrom('invalid'));
    }

    public function testTryFromWithNull(): void
    {
        $this->assertNull(Uuid::tryFrom(null));
    }

    public function testCompareTo(): void
    {
        $uuid1 = UuidV7::make();
        $uuid2 = UuidV7::make();
        $uuid3 = UuidV7::make();

        $expected = [$uuid3->value, $uuid1->value, $uuid2->value];
        usort($expected, fn ($a, $b) => $a->compareTo($b));

        $actual = [$uuid2, $uuid1, $uuid3];
        usort($actual, fn ($a, $b) => $a->compareTo($b));
        $actual = array_map(fn ($id) => $id->value, $actual);

        $this->assertSame($expected, $actual);
    }
}
