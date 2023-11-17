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

use CloudCreativity\Modules\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Toolkit\Identifiers\PossiblyNumericId;
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
