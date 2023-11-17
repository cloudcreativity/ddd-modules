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

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Domain;

use CloudCreativity\BalancedEvent\Common\Domain\EntityInterface;
use CloudCreativity\BalancedEvent\Common\Domain\IdentifierOrEntity;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\Guid;
use PHPUnit\Framework\TestCase;

class IdentifierOrEntityTest extends TestCase
{
    /**
     * @return void
     */
    public function testItIsAGuid(): void
    {
        $guidOrEntity = IdentifierOrEntity::make($guid = Guid::fromInteger('SomeType', 1));

        $this->assertSame($guid, $guidOrEntity->id());
        $this->assertSame($guid, $guidOrEntity->idOrFail());
        $this->assertNull($guidOrEntity->entity);
    }

    /**
     * @return void
     */
    public function testItIsAnEntityWithGuid(): void
    {
        $entity = $this->createMock(EntityInterface::class);
        $entity->method('getId')->willReturn($guid = Guid::fromInteger('SomeType', 1));

        $guidOrEntity = IdentifierOrEntity::make($entity);

        $this->assertSame($guid, $guidOrEntity->id());
        $this->assertSame($guid, $guidOrEntity->idOrFail());
        $this->assertSame($entity, $guidOrEntity->entity);
    }

    /**
     * @return void
     */
    public function testItIsAnEntityWithoutGuid(): void
    {
        $entity = $this->createMock(EntityInterface::class);
        $entity->method('getId')->willReturn(null);

        $guidOrEntity = IdentifierOrEntity::make($entity);

        $this->assertNull($guidOrEntity->id());
        $this->assertSame($entity, $guidOrEntity->entity);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Entity does not have an identifier.');

        $guidOrEntity->idOrFail();
    }
}
