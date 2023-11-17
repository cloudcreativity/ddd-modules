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

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Pipeline;

use CloudCreativity\Modules\Toolkit\Pipeline\AccumulationProcessor;
use PHPUnit\Framework\TestCase;

class AccumulationProcessorTest extends TestCase
{
    public function test(): void
    {
        $input = new \DateTime();

        $processor = new AccumulationProcessor(
            static fn (?int $carry, int $result) => intval($carry) + $result,
        );

        $a = function ($actual) use ($input): int {
            $this->assertSame($actual, $input);
            return 1;
        };

        $b = function ($actual) use ($input): int {
            $this->assertSame($actual, $input);
            return 2;
        };

        $c = function ($actual) use ($input): int {
            $this->assertSame($actual, $input);
            return 3;
        };

        $result = $processor->process($input, $a, $b, $c);

        $this->assertSame(6, $result);
    }

    public function testWithInitialValue(): void
    {
        $input = new \DateTime();

        $processor = new AccumulationProcessor(
            static fn (int $carry, int $result) => $carry + $result,
            10,
        );

        $a = function ($actual) use ($input): int {
            $this->assertSame($actual, $input);
            return 1;
        };

        $b = function ($actual) use ($input): int {
            $this->assertSame($actual, $input);
            return 2;
        };

        $c = function ($actual) use ($input): int {
            $this->assertSame($actual, $input);
            return 3;
        };

        $result = $processor->process($input, $a, $b, $c);

        $this->assertSame(16, $result);
    }

    public function testNoStagesWithoutInitialValue(): void
    {
        $processor = new AccumulationProcessor(function () {
            $this->fail('Callback should not be executed on an empty stack.');
        });

        $this->assertNull($processor->process('foobar'));
    }

    public function testNoStagesWithInitialValue(): void
    {
        $processor = new AccumulationProcessor(function () {
            $this->fail('Callback should not be executed on an empty stack.');
        }, 10);

        $this->assertSame(10, $processor->process('foobar'));
    }
}
