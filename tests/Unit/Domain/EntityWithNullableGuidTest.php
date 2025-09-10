<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Domain;

use CloudCreativity\Modules\Contracts\Domain\Entity;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Toolkit\ContractException;
use CloudCreativity\Modules\Toolkit\Identifiers\Guid;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class EntityWithNullableGuidTest extends TestCase
{
    public function test(): void
    {
        $entity = new TestEntityWithNullableId(
            $guid = Guid::fromInteger('SomeType', 1),
        );

        $this->assertSame($guid, $entity->getId());
        $this->assertSame($guid, $entity->getIdOrFail());
        $this->assertFalse($entity->is(null));
        $this->assertTrue($entity->isNot(null));
        $this->assertTrue($entity->hasId());
    }

    public function testWithNullId(): TestEntityWithNullableId
    {
        $entity = new TestEntityWithNullableId();

        $this->assertNull($entity->getId());
        $this->assertFalse($entity->is(null));
        $this->assertTrue($entity->isNot(null));
        $this->assertFalse($entity->hasId());

        return $entity;
    }

    #[Depends('testWithNullId')]
    public function testGetIdOrFailWithoutId(TestEntityWithNullableId $entity): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Entity does not have an identifier.');

        $entity->getIdOrFail();
    }

    public function testSetId(): void
    {
        $entity = new TestEntityWithNullableId();
        $actual = $entity->setId($guid = Guid::fromInteger('SomeType', 99));

        $this->assertSame($entity, $actual);
        $this->assertSame($guid, $entity->getId());
    }

    public function testSetIdWhenEntityHasGuid(): void
    {
        $entity = new TestEntityWithNullableId(Guid::fromInteger('SomeType', 1));

        $this->expectException(ContractException::class);
        $entity->setId(Guid::fromInteger('SomeType', 2));
    }

    public function testItIsTheSame(): void
    {
        $a = new TestEntityWithNullableId(
            $id1 = $this->createMock(Identifier::class),
        );

        $b = new TestEntityWithNullableId(
            $id2 = $this->createMock(Identifier::class),
        );

        $id1
            ->expects($this->exactly(2))
            ->method('is')
            ->with($this->identicalTo($id2))
            ->willReturn(true);

        $this->assertTrue($a->is($b));
        $this->assertFalse($a->isNot($b));
    }

    public function testItIsNotTheSameWithNullId(): void
    {
        $a = new TestEntityWithNullableId();

        $b = new TestEntityWithNullableId(
            $id1 = $this->createMock(Identifier::class),
        );

        $id1
            ->expects($this->never())
            ->method('is');

        $this->assertFalse($a->is($b));
        $this->assertTrue($a->isNot($b));
    }

    public function testItIsTheSameWhenOtherHasNullId(): void
    {
        $a = new TestEntityWithNullableId(
            $id1 = $this->createMock(Identifier::class),
        );

        $b = new TestEntityWithNullableId();

        $id1
            ->expects($this->exactly(2))
            ->method('is')
            ->with(null)
            ->willReturn(false);

        $this->assertFalse($a->is($b));
        $this->assertTrue($a->isNot($b));
    }

    public function testItIsNotTheSame(): void
    {
        $a = new TestEntityWithNullableId(
            $id1 = $this->createMock(Identifier::class),
        );

        $b = new TestEntityWithNullableId(
            $id2 = $this->createMock(Identifier::class),
        );

        $id1
            ->expects($this->exactly(2))
            ->method('is')
            ->with($this->identicalTo($id2))
            ->willReturn(false);

        $this->assertFalse($a->is($b));
        $this->assertTrue($a->isNot($b));
    }

    public function testItIsDifferentClass(): void
    {
        $a = new TestEntityWithNullableId(
            $id1 = $this->createMock(Identifier::class),
        );

        $b = $this->createMock(Entity::class);
        $b->method('getId')->willReturn($this->createMock(Identifier::class));

        $id1
            ->expects($this->never())
            ->method('is');

        $this->assertFalse($a->is($b));
        $this->assertTrue($a->isNot($b));
    }
}
