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

use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\InterruptibleProcessor;
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
