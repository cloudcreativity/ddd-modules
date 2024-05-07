<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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
use CloudCreativity\Modules\Toolkit\Identifiers\ListOfIdentifiers;
use CloudCreativity\Modules\Toolkit\Identifiers\StringId;
use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid as RamseyUuid;

class ListOfIdentifiersTest extends TestCase
{
    /**
     * @return void
     */
    public function testItIsListOfGuids(): void
    {
        $ids = new ListOfIdentifiers(
            $a = Guid::fromInteger('SomeType', 1),
            $b = Guid::fromInteger('SomeType', 2),
            $c = Guid::fromString('SomeOtherType', '3'),
        );

        $expected = [$a, $b, $c];

        $this->assertCount(3, $ids);
        $this->assertSame($expected, iterator_to_array($ids));
        $this->assertSame($expected, $ids->all());
        $this->assertSame($expected, iterator_to_array($ids->guids()));
    }

    /**
     * @return void
     */
    public function testItContainsIdsThatAreNotGuids(): void
    {
        $ids = new ListOfIdentifiers(
            Guid::fromInteger('SomeType', 1),
            $this->createMock(Identifier::class),
            Guid::fromString('SomeOtherType', '3'),
        );

        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Unexpected identifier type');

        iterator_to_array($ids->guids());
    }

    /**
     * @return void
     */
    public function testItIsListOfIntegerIds(): void
    {
        $ids = new ListOfIdentifiers(
            $a = IntegerId::from(1),
            $b = IntegerId::from(2),
            $c = IntegerId::from(3),
        );

        $expected = [$a, $b, $c];

        $this->assertCount(3, $ids);
        $this->assertSame($expected, iterator_to_array($ids));
        $this->assertSame($expected, $ids->all());
        $this->assertSame($expected, iterator_to_array($ids->integerIds()));
        $this->assertSame([1, 2, 3], $ids->integerIds()->toBase());
    }

    /**
     * @return void
     */
    public function testItContainsIdsThatAreNotIntegerIds(): void
    {
        $ids = new ListOfIdentifiers(
            IntegerId::from(1),
            $this->createMock(Identifier::class),
            IntegerId::from(3),
        );

        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Unexpected identifier type');

        iterator_to_array($ids->integerIds());
    }

    /**
     * @return void
     */
    public function testItIsListOfStringIds(): void
    {
        $ids = new ListOfIdentifiers(
            $a = StringId::from('1'),
            $b = StringId::from('2'),
            $c = StringId::from('3'),
        );

        $expected = [$a, $b, $c];

        $this->assertCount(3, $ids);
        $this->assertSame($expected, iterator_to_array($ids));
        $this->assertSame($expected, $ids->all());
        $this->assertSame($expected, iterator_to_array($ids->stringIds()));
        $this->assertSame(['1', '2', '3'], $ids->stringIds()->toBase());
    }

    /**
     * @return void
     */
    public function testItContainsIdsThatAreNotStringIds(): void
    {
        $ids = new ListOfIdentifiers(
            StringId::from('1'),
            $this->createMock(Identifier::class),
            StringId::from('3'),
        );

        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Unexpected identifier type');

        iterator_to_array($ids->stringIds());
    }

    /**
     * @return void
     */
    public function testItIsListOfUuids(): void
    {
        $ids = new ListOfIdentifiers(
            $a = Uuid::from($uuid1 = RamseyUuid::uuid4()),
            $b = Uuid::from($uuid2 = RamseyUuid::uuid4()),
            $c = Uuid::from($uuid3 = RamseyUuid::uuid4()),
        );

        $expected = [$a, $b, $c];

        $this->assertCount(3, $ids);
        $this->assertSame($expected, iterator_to_array($ids));
        $this->assertSame($expected, $ids->all());
        $this->assertSame($expected, iterator_to_array($ids->uuids()));
        $this->assertSame([$uuid1, $uuid2, $uuid3], $ids->uuids()->toBase());
    }

    /**
     * @return void
     */
    public function testItContainsIdsThatAreNotUuids(): void
    {
        $ids = new ListOfIdentifiers(
            Uuid::from(RamseyUuid::uuid4()),
            $this->createMock(Identifier::class),
            Uuid::from(RamseyUuid::uuid4()),
        );

        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Unexpected identifier type');

        iterator_to_array($ids->uuids());
    }

    /**
     * @return void
     */
    public function testItIsNotEmpty(): void
    {
        $ids = new ListOfIdentifiers(
            Guid::fromInteger('SomeType', 1),
            Guid::fromInteger('SomeType', 2),
            Guid::fromString('SomeOtherType', '3'),
        );

        $this->assertCount(3, $ids);
        $this->assertFalse($ids->isEmpty());
        $this->assertTrue($ids->isNotEmpty());
    }

    /**
     * @return void
     */
    public function testItIsEmpty(): void
    {
        $ids = new ListOfIdentifiers();

        $this->assertCount(0, $ids);
        $this->assertTrue($ids->isEmpty());
        $this->assertFalse($ids->isNotEmpty());
    }
}
