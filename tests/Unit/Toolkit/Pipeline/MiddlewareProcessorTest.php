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
