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
use CloudCreativity\Modules\Domain\IdentifierOrEntity;
use CloudCreativity\Modules\Toolkit\Identifiers\Guid;
use PHPUnit\Framework\TestCase;

class IdentifierOrEntityTest extends TestCase
{
    public function testItIsAGuid(): void
    {
        $guidOrEntity = IdentifierOrEntity::make($guid = Guid::fromInteger('SomeType', 1));

        $this->assertSame($guid, $guidOrEntity->id());
        $this->assertSame($guid, $guidOrEntity->idOrFail());
        $this->assertNull($guidOrEntity->entity);
    }

    public function testItIsAnEntityWithGuid(): void
    {
        $entity = $this->createMock(Entity::class);
        $entity->method('getId')->willReturn($guid = Guid::fromInteger('SomeType', 1));

        $guidOrEntity = IdentifierOrEntity::make($entity);

        $this->assertSame($guid, $guidOrEntity->id());
        $this->assertSame($guid, $guidOrEntity->idOrFail());
        $this->assertSame($entity, $guidOrEntity->entity);
    }

    public function testItIsAnEntityWithoutGuid(): void
    {
        $entity = $this->createMock(Entity::class);
        $entity->method('getId')->willReturn(null);

        $guidOrEntity = IdentifierOrEntity::make($entity);

        $this->assertNull($guidOrEntity->id());
        $this->assertSame($entity, $guidOrEntity->entity);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Entity does not have an identifier.');

        $guidOrEntity->idOrFail();
    }
}
