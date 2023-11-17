<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
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
