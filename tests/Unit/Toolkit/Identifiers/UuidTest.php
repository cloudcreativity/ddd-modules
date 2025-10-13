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
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\UuidFactory;
use CloudCreativity\Modules\Toolkit\ContractException;
use CloudCreativity\Modules\Toolkit\Identifiers\Guid;
use CloudCreativity\Modules\Toolkit\Identifiers\IntegerId;
use CloudCreativity\Modules\Toolkit\Identifiers\StringId;
use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid as RamseyUuid;

class UuidTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Uuid::setFactory(null);
    }

    public function test(): void
    {
        $base = RamseyUuid::uuid4();
        $id = new Uuid($base);

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

        $this->assertObjectEquals($id = new Uuid($base), $other = Uuid::from($base));
        $this->assertSame($id, Uuid::from($id));
        $this->assertTrue($id->is($other));
        $this->assertTrue($id->any(null, Uuid::random(), $other));
        $this->assertEquals($id, Uuid::tryFrom($base));
        $this->assertSame($id, Uuid::tryFrom($id));
    }

    public function testItIsNotEqual(): void
    {
        $id = new Uuid(RamseyUuid::fromString('6dcbad65-ed92-4e60-973b-9ba58a022816'));
        $this->assertFalse($id->equals($other = new Uuid(
            RamseyUuid::fromString('38c7be26-6887-4742-8b6b-7d07b30ca596'),
        )));
        $this->assertFalse($id->is($other));
        $this->assertFalse($id->any(null, Uuid::random(), $other));
    }

    /**
     * @return array<int, array<Identifier|null>>
     */
    public static function notUuidProvider(): array
    {
        return [
            [null],
            [new IntegerId(1)],
            [new StringId('foo')],
            [new Guid('SomeType', new Uuid(RamseyUuid::fromString('6dcbad65-ed92-4e60-973b-9ba58a022816')))],
        ];
    }

    #[DataProvider('notUuidProvider')]
    public function testIsWithOtherIdentifiers(?Identifier $other): void
    {
        $id = new Uuid(RamseyUuid::fromString('6dcbad65-ed92-4e60-973b-9ba58a022816'));

        $this->assertFalse($id->is($other));
        $this->assertFalse($id->any(null, Uuid::random(), $other));
        $this->assertFalse($id->any());
    }

    #[DataProvider('notUuidProvider')]
    public function testFromWithOtherIdentifiers(?Identifier $other): void
    {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Unexpected identifier type, received: ' . get_debug_type($other));
        Uuid::from($other);
    }

    #[DataProvider('notUuidProvider')]
    public function testTryFromWithOtherIdentifiers(?Identifier $other): void
    {
        $this->assertNull(Uuid::tryFrom($other));
    }

    public function testFromWithString(): void
    {
        Uuid::setFactory($factory = $this->createMock(UuidFactory::class));

        $factory
            ->expects($this->once())
            ->method('fromString')
            ->with($str = 'blah!')
            ->willReturn($expected = new Uuid(RamseyUuid::uuid4()));

        $this->assertSame($expected, Uuid::from($str));
    }

    public function testTryFromWithString(): void
    {
        Uuid::setFactory($factory = $this->createMock(UuidFactory::class));

        $factory
            ->expects($this->once())
            ->method('fromString')
            ->with($valid = '10562dcc-faf0-4444-91b9-a9d374e5501f')
            ->willReturn($expected = new Uuid(RamseyUuid::fromString('10562dcc-faf0-4444-91b9-a9d374e5501f')));

        $this->assertSame($expected, Uuid::tryFrom($valid));
        $this->assertNull(Uuid::tryFrom('invalid'));
    }

    public function testFromAndTryFromWithBaseUuid(): void
    {
        Uuid::setFactory($factory = $this->createMock(UuidFactory::class));

        $factory
            ->expects($this->exactly(2))
            ->method('from')
            ->with($this->identicalTo($base = RamseyUuid::uuid4()))
            ->willReturn($expected = new Uuid($base));

        $this->assertSame($expected, Uuid::from($base));
        $this->assertSame($expected, Uuid::tryFrom($base));
    }

    public function testTryFromWithNull(): void
    {
        Uuid::setFactory($factory = $this->createMock(UuidFactory::class));

        $factory
            ->expects($this->never())
            ->method($this->anything());

        $this->assertNull(Uuid::tryFrom(null));
    }

    public function testNil(): void
    {
        $base = RamseyUuid::fromString(RamseyUuid::NIL);
        $actual = Uuid::nil();

        $this->assertTrue($actual->value->equals($base));
    }
}
