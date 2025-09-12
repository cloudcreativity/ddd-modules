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

use Closure;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use PHPUnit\Framework\TestCase;

class MiddlewareProcessorTest extends TestCase
{
    public function test(): void
    {
        $processor = new MiddlewareProcessor();

        $result = $processor->process(
            [],
            $this->createMiddleware('A'),
            $this->createMiddleware('B'),
            $this->createMiddleware('C'),
        );

        $this->assertSame(['A1', 'B1', 'C1', 'C2', 'B2', 'A2'], $result);
    }

    public function testWithDestination(): void
    {
        $processor = new MiddlewareProcessor(
            static fn (array $values): array => array_map(
                static fn (string $v): string => strtolower($v), /** @phpstan-ignore-line */
                $values,
            ),
        );

        $result = $processor->process(
            [],
            $this->createMiddleware('A'),
            $this->createMiddleware('B'),
            $this->createMiddleware('C'),
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
            static fn (string $value): string => strtoupper($value),
        );

        $result = $processor->process('foo');

        $this->assertSame('FOO', $result);
    }

    private function createMiddleware(string $step): Closure
    {
        return static function (array $values, Closure $next) use ($step) {
            $values[] = "{$step}1";
            $result = $next($values);
            assert(is_array($result));
            $result[] = "{$step}2";
            return $result;
        };
    }
}
