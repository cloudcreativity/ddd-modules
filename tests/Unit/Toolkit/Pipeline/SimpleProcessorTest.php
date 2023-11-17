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

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Toolkit\Pipeline;

use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\SimpleProcessor;
use PHPUnit\Framework\TestCase;

class SimpleProcessorTest extends TestCase
{
    public function test(): void
    {
        $processor = new SimpleProcessor();

        $a = static fn(int $value): int => $value * 2;
        $b = static fn(int $value): int => $value + 1;
        $c = static fn(int $value): int => $value * 3;

        $result = $processor->process(10, $a, $b, $c);

        $this->assertSame(63, $result);
    }

    public function testNoStages(): void
    {
        $processor = new SimpleProcessor();

        $result = $processor->process(10);

        $this->assertSame(10, $result);
    }
}
