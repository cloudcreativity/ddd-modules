<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Loggable;

use CloudCreativity\Modules\Contracts\Toolkit\Loggable\ContextProvider;
use CloudCreativity\Modules\Toolkit\Loggable\ObjectContext;
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
        $source = new class () implements ContextProvider {
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
