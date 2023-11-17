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
