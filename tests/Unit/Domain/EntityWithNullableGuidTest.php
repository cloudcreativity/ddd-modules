<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Domain;

use CloudCreativity\Modules\Domain\EntityInterface;
use CloudCreativity\Modules\Toolkit\ContractException;
use CloudCreativity\Modules\Toolkit\Identifiers\Guid;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;
use PHPUnit\Framework\TestCase;

class EntityWithNullableGuidTest extends TestCase
{
    public function test(): void
    {
        $entity = new TestEntityWithNullableId(
            $guid = Guid::fromInteger('SomeType', 1),
        );

        $this->assertSame($guid, $entity->getId());
        $this->assertFalse($entity->is(null));
        $this->assertTrue($entity->isNot(null));
        $this->assertTrue($entity->hasId());
    }

    public function testWithNullId(): void
    {
        $entity = new TestEntityWithNullableId();

        $this->assertNull($entity->getId());
        $this->assertFalse($entity->is(null));
        $this->assertTrue($entity->isNot(null));
        $this->assertFalse($entity->hasId());
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

    /**
     * @return void
     */
    public function testItIsTheSame(): void
    {
        $a = new TestEntityWithNullableId(
            $id1 = $this->createMock(IdentifierInterface::class),
        );

        $b = new TestEntityWithNullableId(
            $id2 = $this->createMock(IdentifierInterface::class),
        );

        $id1
            ->expects($this->exactly(2))
            ->method('is')
            ->with($this->identicalTo($id2))
            ->willReturn(true);

        $this->assertTrue($a->is($b));
        $this->assertFalse($a->isNot($b));
    }

    /**
     * @return void
     */
    public function testItIsNotTheSameWithNullId(): void
    {
        $a = new TestEntityWithNullableId();

        $b = new TestEntityWithNullableId(
            $id1 = $this->createMock(IdentifierInterface::class),
        );

        $id1
            ->expects($this->never())
            ->method('is');

        $this->assertFalse($a->is($b));
        $this->assertTrue($a->isNot($b));
    }

    /**
     * @return void
     */
    public function testItIsTheSameWhenOtherHasNullId(): void
    {
        $a = new TestEntityWithNullableId(
            $id1 = $this->createMock(IdentifierInterface::class),
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

    /**
     * @return void
     */
    public function testItIsNotTheSame(): void
    {
        $a = new TestEntityWithNullableId(
            $id1 = $this->createMock(IdentifierInterface::class),
        );

        $b = new TestEntityWithNullableId(
            $id2 = $this->createMock(IdentifierInterface::class),
        );

        $id1
            ->expects($this->exactly(2))
            ->method('is')
            ->with($this->identicalTo($id2))
            ->willReturn(false);

        $this->assertFalse($a->is($b));
        $this->assertTrue($a->isNot($b));
    }

    /**
     * @return void
     */
    public function testItIsDifferentClass(): void
    {
        $a = new TestEntityWithNullableId(
            $id1 = $this->createMock(IdentifierInterface::class),
        );

        $b = $this->createMock(EntityInterface::class);
        $b->method('getId')->willReturn($this->createMock(IdentifierInterface::class));

        $id1
            ->expects($this->never())
            ->method('is');

        $this->assertFalse($a->is($b));
        $this->assertTrue($a->isNot($b));
    }
}
