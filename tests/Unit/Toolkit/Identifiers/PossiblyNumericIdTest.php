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

use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\Identifier;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\PossiblyNumericId;
use PHPUnit\Framework\TestCase;

class PossiblyNumericIdTest extends TestCase
{
    /**
     * @return array
     */
    public static function valueProvider(): array
    {
        return [
            [1, 1],
            ['1', 1],
            ['0001', 1],
            ['999', 999],
            ['foobar', 'foobar'],
            [' 0009 ', ' 0009 '],
        ];
    }

    /**
     * @param string|int $value
     * @param string|int $expected
     * @return void
     * @dataProvider valueProvider
     */
    public function test(string|int $value, string|int $expected): void
    {
        $actual = new PossiblyNumericId($value);
        $expectedId = Identifier::make($expected);

        $this->assertSame($expected, $actual->value);
        $this->assertSame($expected, PossiblyNumericId::from($value)->value);
        $this->assertSame((string) $expected, (string) $actual);
        $this->assertSame((string) $expected, $actual->toString());
        $this->assertObjectEquals($expectedId, $actual->toId());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['value' => $expected]),
            json_encode(['value' => $actual]),
        );
    }
}