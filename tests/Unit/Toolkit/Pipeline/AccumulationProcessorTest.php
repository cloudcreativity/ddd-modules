<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Toolkit\Pipeline;

use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\AccumulationProcessor;
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
