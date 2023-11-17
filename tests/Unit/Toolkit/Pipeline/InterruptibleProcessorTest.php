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

use CloudCreativity\Modules\Toolkit\Pipeline\InterruptibleProcessor;
use PHPUnit\Framework\TestCase;

class InterruptibleProcessorTest extends TestCase
{
    public function test(): void
    {
        $processor = new InterruptibleProcessor(
            static fn($value): bool => 0 === (intval($value) % 10),
        );

        $a = static fn(int $value): int => $value * 10;
        $b = static fn(int $value): int => $value * 20;
        $c = static fn(int $value): float => $value / 3;
        $d = function () {
            $this->fail('Processor did not interrupt execution.');
        };

        $result = $processor->process(1, $a, $b, $c, $d);

        $this->assertSame(200 / 3, $result);
    }

    public function testNoStages(): void
    {
        $processor = new InterruptibleProcessor(function () {
            $this->fail('Not expecting checker function to be called');
        });

        $result = $processor->process(10);

        $this->assertSame(10, $result);
    }
}
