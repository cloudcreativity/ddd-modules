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
