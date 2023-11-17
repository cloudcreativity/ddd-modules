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

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Toolkit\Identifiers;

use CloudCreativity\BalancedEvent\Common\Toolkit\ContractException;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\Guid;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\IdentifierInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\IntegerId;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\StringId;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\Uuid;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid as RamseyUuid;

class UuidTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $base = RamseyUuid::uuid4();
        $id = new Uuid($base);

        $this->assertSame($base, $id->value);
        $this->assertSame($base->toString(), $id->context());
        $this->assertSame($base->toString(), $id->key());
        $this->assertSame($base->toString(), $id->toString());
        $this->assertSame((string) $base, (string) $id);
        $this->assertJsonStringEqualsJsonString(
            json_encode(['id' => $base], \JSON_THROW_ON_ERROR),
            json_encode(compact('id'), \JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return void
     */
    public function testItIsEquals(): void
    {
        $base = RamseyUuid::uuid4();

        $this->assertObjectEquals($id = new Uuid($base), $other = Uuid::from($base));
        $this->assertSame($id, Uuid::from($id));
        $this->assertTrue($id->is($other));
    }

    /**
     * @return void
     */
    public function testItIsNotEqual(): void
    {
        $id = new Uuid(RamseyUuid::fromString('6dcbad65-ed92-4e60-973b-9ba58a022816'));
        $this->assertFalse($id->equals($other = new Uuid(
            RamseyUuid::fromString('38c7be26-6887-4742-8b6b-7d07b30ca596'),
        )));
        $this->assertFalse($id->is($other));
    }

    /**
     * @return array<int, array<IdentifierInterface>>
     */
    public function notUuidProvider(): array
    {
        return [
            [new IntegerId(1)],
            [new StringId('foo')],
            [new Guid('SomeType', new Uuid(RamseyUuid::fromString('6dcbad65-ed92-4e60-973b-9ba58a022816')))],
        ];
    }

    /**
     * @param IdentifierInterface $other
     * @return void
     * @dataProvider notUuidProvider
     */
    public function testIsWithOtherIdentifiers(IdentifierInterface $other): void
    {
        $id = new Uuid(RamseyUuid::fromString('6dcbad65-ed92-4e60-973b-9ba58a022816'));

        $this->assertFalse($id->is($other));
    }

    /**
     * @return void
     */
    public function testIsWithNull(): void
    {
        $id = new Uuid(RamseyUuid::uuid4());

        $this->assertFalse($id->is(null));
    }

    /**
     * @param IdentifierInterface $other
     * @return void
     * @dataProvider notUuidProvider
     */
    public function testFromWithOtherIdentifiers(IdentifierInterface $other): void
    {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Unexpected identifier type, received: ' . get_debug_type($other));
        Uuid::from($other);
    }
}
