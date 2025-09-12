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

use CloudCreativity\Modules\Toolkit\Pipeline\InterruptibleProcessor;
use PHPUnit\Framework\TestCase;

class InterruptibleProcessorTest extends TestCase
{
    public function test(): void
    {
        $processor = new InterruptibleProcessor(
            static fn (float|int $value): bool => 0 === (intval($value) % 10),
        );

        $a = static fn (int $value): int => $value * 10;
        $b = static fn (int $value): int => $value * 20;
        $c = static fn (int $value): float => $value / 3;
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
