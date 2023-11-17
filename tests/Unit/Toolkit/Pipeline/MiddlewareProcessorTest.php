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

use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\MiddlewareProcessor;
use PHPUnit\Framework\TestCase;

class MiddlewareProcessorTest extends TestCase
{
    /**
     * @var \Closure
     */
    private \Closure $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = static function (string $step): \Closure {
            return static function (array $values, \Closure $next) use ($step) {
                $values[] = "{$step}1";
                $result = $next($values);
                $result[] = "{$step}2";
                return $result;
            };
        };
    }

    public function test(): void
    {
        $processor = new MiddlewareProcessor();

        $result = $processor->process(
            [],
            ($this->middleware)('A'),
            ($this->middleware)('B'),
            ($this->middleware)('C'),
        );

        $this->assertSame(['A1', 'B1', 'C1', 'C2', 'B2', 'A2'], $result);
    }

    public function testWithDestination(): void
    {
        $processor = new MiddlewareProcessor(
            static fn(array $values): array => array_map('strtolower', $values)
        );

        $result = $processor->process(
            [],
            ($this->middleware)('A'),
            ($this->middleware)('B'),
            ($this->middleware)('C'),
        );

        $this->assertSame(['a1', 'b1', 'c1', 'C2', 'B2', 'A2'], $result);
    }

    public function testNoStages(): void
    {
        $processor = new MiddlewareProcessor();

        $result = $processor->process('foo');

        $this->assertSame('foo', $result);
    }

    public function testNoStagesWithDestination(): void
    {
        $processor = new MiddlewareProcessor(
            static fn(string $value): string => strtoupper($value),
        );

        $result = $processor->process('foo');

        $this->assertSame('FOO', $result);
    }
}
