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
use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use CloudCreativity\Modules\Toolkit\Identifiers\UuidFactory;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Type\Integer;
use Ramsey\Uuid\Uuid as BaseUuid;
use Ramsey\Uuid\UuidFactory as BaseUuidFactory;
use Ramsey\Uuid\UuidFactoryInterface as BaseUuidFactoryInterface;

class UuidFactoryTest extends TestCase
{
    /**
     * @var MockObject&BaseUuidFactory
     */
    private BaseUuidFactory&MockObject $baseFactory;

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
            $this->baseFactory = $this->createMock(BaseUuidFactory::class),
        );
    }

    /**
     * @return void
     */
    public function testFromWithBaseUuid(): void
    {
        $this->baseFactory
            ->expects($this->never())
            ->method($this->anything());

        $uuid = $this->factory->from($base = BaseUuid::uuid4());

        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame($base, $uuid->value);
    }

    /**
     * @return void
     */
    public function testFromWithUuid(): void
    {
        $this->baseFactory
            ->expects($this->never())
            ->method($this->anything());

        $actual = $this->factory->from($expected = Uuid::random());

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testFromWithIdentifierInterface(): void
    {
        $this->baseFactory
            ->expects($this->never())
            ->method($this->anything());

        $this->expectException(ContractException::class);

        $this->factory->from(
            $this->createMock(Identifier::class),
        );
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

    /**
     * @return void
     */
    public function testUuid7(): void
    {
        $date = new \DateTimeImmutable();

        $this->baseFactory
            ->expects($this->once())
            ->method('uuid7')
            ->with($this->identicalTo($date))
            ->willReturn($base = BaseUuid::uuid4());

        $uuid = $this->factory->uuid7($date);

        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame($uuid->value, $base);
    }

    /**
     * @return void
     */
    public function testUuid7NotSupported(): void
    {
        $factory = new UuidFactory(
            $this->createMock(BaseUuidFactoryInterface::class),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('UUID version 7 is not supported by the underlying factory.');

        $factory->uuid7();
    }

    /**
     * @return void
     */
    public function testUuid8(): void
    {
        $bytes = 'blah!';

        $this->baseFactory
            ->expects($this->once())
            ->method('uuid8')
            ->with($bytes)
            ->willReturn($base = BaseUuid::uuid4());

        $uuid = $this->factory->uuid8($bytes);

        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame($uuid->value, $base);
    }

    /**
     * @return void
     */
    public function testUuid8NotSupported(): void
    {
        $factory = new UuidFactory(
            $this->createMock(BaseUuidFactoryInterface::class),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('UUID version 8 is not supported by the underlying factory.');

        $factory->uuid8('bytes!');
    }
}
