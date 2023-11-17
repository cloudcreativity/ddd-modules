<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
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