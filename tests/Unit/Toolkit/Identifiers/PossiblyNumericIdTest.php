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

use CloudCreativity\Modules\Toolkit\Identifiers\IntegerId;
use CloudCreativity\Modules\Toolkit\Identifiers\PossiblyNumericId;
use CloudCreativity\Modules\Toolkit\Identifiers\StringId;
use PHPUnit\Framework\TestCase;

class PossiblyNumericIdTest extends TestCase
{
    /**
     * @return array<array<int, mixed>>
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
        $expectedId = is_string($expected) ? new StringId($expected) : new IntegerId($expected);

        $this->assertSame($expected, $actual->value);
        $this->assertSame($expected, PossiblyNumericId::from($value)->value);
        $this->assertSame((string) $expected, (string) $actual);
        $this->assertSame((string) $expected, $actual->toString());
        $this->assertObjectEquals($expectedId, $actual->toId());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['value' => $expected], JSON_THROW_ON_ERROR),
            json_encode(['value' => $actual], JSON_THROW_ON_ERROR),
        );
    }
}
