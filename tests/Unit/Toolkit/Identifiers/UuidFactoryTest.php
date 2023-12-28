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

use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use CloudCreativity\Modules\Toolkit\Identifiers\UuidFactory;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Type\Integer;
use Ramsey\Uuid\Uuid as BaseUuid;
use Ramsey\Uuid\UuidFactoryInterface as BaseUuidFactoryInterface;

class UuidFactoryTest extends TestCase
{
    /**
     * @var MockObject&BaseUuidFactoryInterface
     */
    private BaseUuidFactoryInterface&MockObject $baseFactory;

    /**
     * @var UuidFactory
     */
    private UuidFactory $factory;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new UuidFactory(
            $this->baseFactory = $this->createMock(BaseUuidFactoryInterface::class),
        );
    }

    /**
     * @return void
     */
    public function testFrom(): void
    {
        $this->baseFactory
            ->expects($this->never())
            ->method($this->anything());

        $uuid = $this->factory->from($base = BaseUuid::uuid4());

        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame($uuid->value, $base);
    }

    /**
     * @return void
     */
    public function testFromBytes(): void
    {
        $this->baseFactory
            ->expects($this->once())
            ->method('fromBytes')
            ->with($bytes = 'blah!')
            ->willReturn($base = BaseUuid::uuid4());

        $uuid = $this->factory->fromBytes($bytes);

        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame($uuid->value, $base);
    }

    /**
     * @return void
     */
    public function testFromDateTime(): void
    {
        $this->baseFactory
            ->expects($this->once())
            ->method('fromDateTime')
            ->with(
                $this->identicalTo($dateTime = new DateTimeImmutable('2021-10-10')),
                $this->identicalTo($hex = new Hexadecimal('8f0b0445be')),
                $int = 99,
            )
            ->willReturn($base = BaseUuid::uuid4());

        $uuid = $this->factory->fromDateTime($dateTime, $hex, $int);

        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame($uuid->value, $base);
    }

    /**
     * @return void
     */
    public function testFromInteger(): void
    {
        $this->baseFactory
            ->expects($this->once())
            ->method('fromInteger')
            ->with($integer = '123')
            ->willReturn($base = BaseUuid::uuid4());

        $uuid = $this->factory->fromInteger($integer);

        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame($uuid->value, $base);
    }

    /**
     * @return void
     */
    public function testFromString(): void
    {
        $this->baseFactory
            ->expects($this->once())
            ->method('fromString')
            ->with($string = 'foobar')
            ->willReturn($base = BaseUuid::uuid4());

        $uuid = $this->factory->fromString($string);

        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame($uuid->value, $base);
    }

    /**
     * @return void
     */
    public function testUuid1(): void
    {
        $this->baseFactory
            ->expects($this->once())
            ->method('uuid1')
            ->with(
                $this->identicalTo($hex = new Hexadecimal('8f0b0445be')),
                $int = 99,
            )
            ->willReturn($base = BaseUuid::uuid4());

        $uuid = $this->factory->uuid1($hex, $int);

        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame($uuid->value, $base);
    }

    /**
     * @return void
     */
    public function testUuid2(): void
    {
        $this->baseFactory
            ->expects($this->once())
            ->method('uuid2')
            ->with(
                $int = 99,
                $this->identicalTo($integer = new Integer(123)),
                $this->identicalTo($hex = new Hexadecimal('8f0b0445be')),
                $seq = 999,
            )
            ->willReturn($base = BaseUuid::uuid4());

        $uuid = $this->factory->uuid2($int, $integer, $hex, $seq);

        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame($uuid->value, $base);
    }

    /**
     * @return void
     */
    public function testUuid3(): void
    {
        $this->baseFactory
            ->expects($this->once())
            ->method('uuid3')
            ->with(
                $this->identicalTo($ns = BaseUuid::uuid4()),
                $name = 'foobar',
            )
            ->willReturn($base = BaseUuid::uuid4());

        $uuid = $this->factory->uuid3($ns, $name);

        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame($uuid->value, $base);
    }

    /**
     * @return void
     */
    public function testUuid4(): void
    {
        $this->baseFactory
            ->expects($this->once())
            ->method('uuid4')
            ->willReturn($base = BaseUuid::uuid4());

        $uuid = $this->factory->uuid4();

        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame($uuid->value, $base);
    }

    /**
     * @return void
     */
    public function testUuid5(): void
    {
        $this->baseFactory
            ->expects($this->once())
            ->method('uuid5')
            ->with(
                $this->identicalTo($ns = BaseUuid::uuid4()),
                $name = 'foobar',
            )
            ->willReturn($base = BaseUuid::uuid4());

        $uuid = $this->factory->uuid5($ns, $name);

        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame($uuid->value, $base);
    }

    /**
     * @return void
     */
    public function testUuid6(): void
    {
        $this->baseFactory
            ->expects($this->once())
            ->method('uuid6')
            ->with(
                $this->identicalTo($hex = new Hexadecimal('8f0b0445be')),
                $int = 99,
            )
            ->willReturn($base = BaseUuid::uuid4());

        $uuid = $this->factory->uuid6($hex, $int);

        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame($uuid->value, $base);
    }
}
