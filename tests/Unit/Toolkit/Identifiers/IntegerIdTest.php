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
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;
use CloudCreativity\Modules\Toolkit\Identifiers\IntegerId;
use CloudCreativity\Modules\Toolkit\Identifiers\StringId;
use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use PHPUnit\Framework\TestCase;

class IntegerIdTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $id = new IntegerId(99);

        $this->assertSame(99, $id->value);
        $this->assertSame(99, $id->context());
        $this->assertSame(99, $id->key());
        $this->assertSame('99', $id->toString());
        $this->assertSame('99', (string) $id);
        $this->assertJsonStringEqualsJsonString(
            json_encode(['id' => 99], \JSON_THROW_ON_ERROR),
            json_encode(compact('id'), \JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return void
     */
    public function testItMustBeGreaterThanZero(): void
    {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Identifier value must be greater than zero.');
        new IntegerId(0);
    }

    /**
     * @return void
     */
    public function testItIsEquals(): void
    {
        $this->assertObjectEquals($id = new IntegerId(99), $other = IntegerId::from(99));
        $this->assertSame($id, IntegerId::from($id));
        $this->assertTrue($id->is($other));
    }

    /**
     * @return void
     */
    public function testItIsNotEqual(): void
    {
        $id = new IntegerId(99);
        $this->assertFalse($id->equals($other = new IntegerId(100)));
        $this->assertFalse($id->is($other));
    }

    /**
     * @return array<int, array<IdentifierInterface>>
     */
    public function notIntegerIdProvider(): array
    {
        return [
            [new StringId('1')],
            [new Guid('SomeType', new IntegerId(1))],
            [new Uuid(\Ramsey\Uuid\Uuid::uuid4())],
        ];
    }

    /**
     * @param IdentifierInterface $other
     * @return void
     * @dataProvider notIntegerIdProvider
     */
    public function testIsWithOtherIdentifiers(IdentifierInterface $other): void
    {
        $id = new IntegerId(1);

        $this->assertFalse($id->is($other));
    }

    /**
     * @return void
     */
    public function testIsWithNull(): void
    {
        $id = new IntegerId(1);

        $this->assertFalse($id->is(null));
    }

    /**
     * @param IdentifierInterface $other
     * @return void
     * @dataProvider notIntegerIdProvider
     */
    public function testFromWithOtherIdentifiers(IdentifierInterface $other): void
    {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Unexpected identifier type, received: ' . get_debug_type($other));
        IntegerId::from($other);
    }
}
