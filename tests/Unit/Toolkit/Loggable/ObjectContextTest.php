<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Loggable;

use CloudCreativity\Modules\Contracts\Toolkit\Loggable\ContextProvider;
use CloudCreativity\Modules\Toolkit\Loggable\ObjectContext;
use CloudCreativity\Modules\Toolkit\Loggable\Sensitive;
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
            public ?string $blah = null;
            protected string $foobar = 'foobar';
        };

        $expected = [
            'foo' => 'bar',
            'baz' => 'bat',
            'blah' => null,
        ];

        $this->assertSame($expected, ObjectContext::from($source)->context());
    }

    /**
     * @return void
     */
    public function testItExcludesSensitiveProperties(): void
    {
        $source = new class ('Hello', 'World') {
            public string $foo = 'bar';
            #[Sensitive]
            public string $baz = 'bat';
            public string $foobar = 'foobar';

            public function __construct(
                #[Sensitive] public string $blah1,
                public string $blah2,
            ) {
            }
        };

        $expected = [
            'foo' => 'bar',
            'foobar' => 'foobar',
            'blah2' => 'World',
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
