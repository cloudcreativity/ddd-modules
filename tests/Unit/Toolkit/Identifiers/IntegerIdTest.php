<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Identifiers;

use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Toolkit\ContractException;
use CloudCreativity\Modules\Toolkit\Identifiers\Guid;
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
     * @return array<int, array<Identifier>>
     */
    public static function notIntegerIdProvider(): array
    {
        return [
            [new StringId('1')],
            [new Guid('SomeType', new IntegerId(1))],
            [new Uuid(\Ramsey\Uuid\Uuid::uuid4())],
        ];
    }

    /**
     * @param Identifier $other
     * @return void
     * @dataProvider notIntegerIdProvider
     */
    public function testIsWithOtherIdentifiers(Identifier $other): void
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
     * @param Identifier $other
     * @return void
     * @dataProvider notIntegerIdProvider
     */
    public function testFromWithOtherIdentifiers(Identifier $other): void
    {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Unexpected identifier type, received: ' . get_debug_type($other));
        IntegerId::from($other);
    }
}
