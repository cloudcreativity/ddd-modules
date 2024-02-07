<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\Log;

use CloudCreativity\Modules\Infrastructure\Log\ContextProviderInterface;
use CloudCreativity\Modules\Infrastructure\Log\ObjectContext;
use PHPUnit\Framework\TestCase;

class ObjectContextTest extends TestCase
{
    /**
     * @return void
     */
    public function testItUsesObjectProperties(): void
    {
        $source = new class () {
            public string $foo = 'bar';
            public string $baz = 'bat';
            protected string $foobar = 'foobar';
        };

        $expected = [
            'foo' => 'bar',
            'baz' => 'bat',
        ];

        $this->assertSame($expected, ObjectContext::from($source)->context());
    }

    /**
     * @return void
     */
    public function testItUsesImplementedContext(): void
    {
        $source = new class () implements ContextProviderInterface {
            public string $foo = 'bar';
            public string $baz = 'bat';

            public function context(): array
            {
                return ['foobar' => 'bazbat'];
            }

        };

        $this->assertSame(['foobar' => 'bazbat'], ObjectContext::from($source)->context());
    }
}
