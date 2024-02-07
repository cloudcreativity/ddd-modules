<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;
use CloudCreativity\Modules\Toolkit\Identifiers\IntegerId;
use CloudCreativity\Modules\Toolkit\Identifiers\LazyListOfIdentifiers;
use CloudCreativity\Modules\Toolkit\Identifiers\StringId;
use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use PHPUnit\Framework\TestCase;

class LazyListOfIdentifiersTest extends TestCase
{
    /**
     * @return void
     */
    public function testItIsListOfGuids(): void
    {
        $a = Guid::fromInteger('SomeType', 1);
        $b = Guid::fromInteger('SomeType', 2);
        $c = Guid::fromString('SomeOtherType', '3');

        $ids = new LazyListOfIdentifiers(function () use ($a, $b, $c) {
            yield $a;
            yield $b;
            yield $c;
        });

        $expected = [$a, $b, $c];

        $this->assertSame($expected, iterator_to_array($ids));
        $this->assertSame($expected, iterator_to_array($ids->guids()));
    }

    /**
     * @return void
     */
    public function testItContainsIdsThatAreNotGuids(): void
    {
        $ids = new LazyListOfIdentifiers(function () {
            yield Guid::fromInteger('SomeType', 1);
            yield $this->createMock(IdentifierInterface::class);
            yield Guid::fromString('SomeOtherType', '3');
        });

        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Unexpected identifier type');

        iterator_to_array($ids->guids());
    }

    /**
     * @return void
     */
    public function testItIsListOfIntegerIds(): void
    {
        $a = IntegerId::from(1);
        $b = IntegerId::from(2);
        $c = IntegerId::from(3);

        $ids = new LazyListOfIdentifiers(function () use ($a, $b, $c) {
            yield $a;
            yield $b;
            yield $c;
        });

        $expected = [$a, $b, $c];

        $this->assertSame($expected, iterator_to_array($ids));
        $this->assertSame($expected, iterator_to_array($ids->integerIds()));
        $this->assertSame([1, 2, 3], $ids->integerIds()->toBase());
    }

    /**
     * @return void
     */
    public function testItContainsIdsThatAreNotIntegerIds(): void
    {
        $ids = new LazyListOfIdentifiers(function () {
            yield IntegerId::from(1);
            yield $this->createMock(IdentifierInterface::class);
            yield IntegerId::from(3);
        });

        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Unexpected identifier type');

        iterator_to_array($ids->integerIds());
    }

    /**
     * @return void
     */
    public function testItIsListOfStringIds(): void
    {
        $a = StringId::from('1');
        $b = StringId::from('2');
        $c = StringId::from('3');

        $ids = new LazyListOfIdentifiers(function () use ($a, $b, $c) {
            yield $a;
            yield $b;
            yield $c;
        });

        $expected = [$a, $b, $c];

        $this->assertSame($expected, iterator_to_array($ids));
        $this->assertSame($expected, iterator_to_array($ids->stringIds()));
        $this->assertSame(['1', '2', '3'], $ids->stringIds()->toBase());
    }

    /**
     * @return void
     */
    public function testItContainsIdsThatAreNotStringIds(): void
    {
        $ids = new LazyListOfIdentifiers(function () {
            yield StringId::from('1');
            yield $this->createMock(IdentifierInterface::class);
            yield StringId::from('3');
        });

        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Unexpected identifier type');

        iterator_to_array($ids->stringIds());
    }

    /**
     * @return void
     */
    public function testItIsListOfUuids(): void
    {
        $a = Uuid::random();
        $b = Uuid::random();
        $c = Uuid::random();

        $ids = new LazyListOfIdentifiers(function () use ($a, $b, $c) {
            yield $a;
            yield $b;
            yield $c;
        });

        $expected = [$a, $b, $c];

        $this->assertSame($expected, iterator_to_array($ids));
        $this->assertSame($expected, iterator_to_array($ids->uuids()));
        $this->assertSame([$a->value, $b->value, $c->value], $ids->uuids()->toBase());
    }

    /**
     * @return void
     */
    public function testItContainsIdsThatAreNotUuids(): void
    {
        $ids = new LazyListOfIdentifiers(function () {
            yield Uuid::random();
            yield $this->createMock(IdentifierInterface::class);
            yield Uuid::random();
        });

        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Unexpected identifier type');

        iterator_to_array($ids->uuids());
    }
}
