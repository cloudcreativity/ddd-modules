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
use CloudCreativity\Modules\Domain\IsEntity;
use CloudCreativity\Modules\Toolkit\Identifiers\Guid;
use CloudCreativity\Modules\Toolkit\Identifiers\IntegerId;
use PHPUnit\Framework\TestCase;

class EntityTest extends TestCase
{
    public function test(): void
    {
        $entity = new TestEntity($guid = Guid::fromInteger('SomeType', 1));

        $this->assertSame($guid, $entity->getId());
        $this->assertSame($guid, $entity->getIdOrFail());
        $this->assertFalse($entity->is(null));
        $this->assertTrue($entity->isNot(null));
    }

    public function testItIsTheSame(): void
    {
        $a = new TestEntity(
            $id1 = $this->createMock(Identifier::class),
        );

        $b = new TestEntity(
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

    public function testItIsNotTheSame(): void
    {
        $a = new TestEntity(
            $id1 = $this->createMock(Identifier::class),
        );

        $b = new TestEntity(
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
        $a = new TestEntity(
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

    public function testItCanUseTraitOnNonReadonlyClass(): void
    {
        $id = new IntegerId(123);

        $entity = new class ($id, 'Bob') implements Entity {
            use IsEntity;

            public function __construct(Identifier $id, private string $name)
            {
                $this->id = $id;
            }

            public function changeName(string $name): void
            {
                $this->name = $name;
            }

            public function getName(): string
            {
                return $this->name;
            }
        };

        $this->assertSame($id, $entity->getId());
        $this->assertSame('Bob', $entity->getName());
        $entity->changeName('Chris');
        $this->assertSame('Chris', $entity->getName());
    }
}
