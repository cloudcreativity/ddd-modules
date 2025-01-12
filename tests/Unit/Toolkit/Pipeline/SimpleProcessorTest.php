<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Pipeline;

use CloudCreativity\Modules\Toolkit\Pipeline\SimpleProcessor;
use PHPUnit\Framework\TestCase;

class SimpleProcessorTest extends TestCase
{
    public function test(): void
    {
        $processor = new SimpleProcessor();

        $a = static fn (int $value): int => $value * 2;
        $b = static fn (int $value): int => $value + 1;
        $c = static fn (int $value): int => $value * 3;

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
