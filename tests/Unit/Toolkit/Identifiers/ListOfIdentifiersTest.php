<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Identifiers;

use CloudCreativity\Modules\Toolkit\Identifiers\Guid;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;
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
            $this->createMock(IdentifierInterface::class),
            Guid::fromString('SomeOtherType', '3'),
        );

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Expecting identifiers to only contain GUIDs.');

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
            $this->createMock(IdentifierInterface::class),
            IntegerId::from(3),
        );

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Expecting identifiers to only contain integer ids.');

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
            $this->createMock(IdentifierInterface::class),
            StringId::from('3'),
        );

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Expecting identifiers to only contain string ids.');

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
            $this->createMock(IdentifierInterface::class),
            Uuid::from(RamseyUuid::uuid4()),
        );

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Expecting identifiers to only contain UUIDs.');

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
