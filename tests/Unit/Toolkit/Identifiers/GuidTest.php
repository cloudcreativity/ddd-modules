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

use CloudCreativity\Modules\Toolkit\ContractException;
use CloudCreativity\Modules\Toolkit\Identifiers\Guid;
use CloudCreativity\Modules\Toolkit\Identifiers\IntegerId;
use CloudCreativity\Modules\Toolkit\Identifiers\StringId;
use PHPUnit\Framework\TestCase;

class GuidTest extends TestCase
{
    public function testStringId(): void
    {
        $guid = Guid::fromString('SomeType', '123');

        $this->assertInstanceOf(\Stringable::class, $guid);
        $this->assertSame('SomeType', $guid->type);
        $this->assertObjectEquals(new StringId('123'), $guid->id);
        $this->assertSame('SomeType:123', $guid->toString());
        $this->assertSame('SomeType:123', (string) $guid);
        $this->assertTrue($guid->isType('SomeType'));
        $this->assertTrue($guid->is($guid));
        $this->assertTrue($guid->is(clone $guid));
        $this->assertFalse($guid->is(Guid::fromInteger('SomeType', 123)));
        $this->assertFalse($guid->is(Guid::fromString('SomeType', '234')));
        $this->assertFalse($guid->is(Guid::fromString('SomeOtherType', '123')));
        $this->assertFalse($guid->is(null));
        $this->assertSame(['type' => 'SomeType', 'id' => '123'], $guid->context());
        $this->assertEquals($guid, Guid::fromString('SomeType', '123'));
        $this->assertObjectEquals($guid, Guid::fromString('SomeType', '123'));
        $this->assertFalse($guid->equals(Guid::fromInteger('SomeType', 123)));
    }

    public function testIntegerId(): void
    {
        $guid = Guid::fromInteger('SomeType', 123);

        $this->assertSame('SomeType', $guid->type);
        $this->assertObjectEquals(new IntegerId(123), $guid->id);
        $this->assertSame('SomeType:123', $guid->toString());
        $this->assertSame('SomeType:123', (string) $guid);
        $this->assertTrue($guid->isType('SomeType'));
        $this->assertTrue($guid->is($guid));
        $this->assertTrue($guid->is(clone $guid));
        $this->assertFalse($guid->is(Guid::fromString('SomeType', '123')));
        $this->assertFalse($guid->is(Guid::fromInteger('SomeType', 234)));
        $this->assertFalse($guid->is(Guid::fromInteger('SomeOtherType', 123)));
        $this->assertFalse($guid->is(null));
        $this->assertSame(['type' => 'SomeType', 'id' => 123], $guid->context());
        $this->assertEquals($guid, Guid::fromInteger('SomeType', 123));
        $this->assertObjectEquals($guid, Guid::fromInteger('SomeType', 123));
        $this->assertFalse($guid->equals(Guid::fromString('SomeType', '123')));
    }

    public function testEmptyType(): void
    {
        $this->expectException(ContractException::class);
        Guid::fromString('', '123');
    }

    public function testEmptyStringId(): void
    {
        $this->expectException(ContractException::class);
        Guid::fromString('SomeType', '');
    }

    public function testNegativeIntegerId(): void
    {
        $this->expectException(ContractException::class);
        Guid::fromInteger('SomeType', -1);
    }

    public function testFromInteger(): void
    {
        $guid = Guid::fromInteger('SomeType', 1);
        $this->assertObjectEquals(new IntegerId(1), $guid->id);
    }

    public function testFromString(): void
    {
        $guid = Guid::fromString('SomeType', '1');
        $this->assertObjectEquals(new StringId('1'), $guid->id);
    }

    /**
     * @return void
     */
    public function testAssertTypeDoesNotThrowForExpectedType(): void
    {
        $guid = Guid::fromInteger('Event', 1);

        $actual = $guid->assertType('Event');
        $this->assertSame($guid, $actual);
    }

    /**
     * @return void
     */
    public function testAssertTypeDoesThrowForUnexpectedType(): void
    {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Expecting type "Foo", received "Event".');

        Guid::fromInteger('Event', 1)->assertType('Foo');
    }
}
