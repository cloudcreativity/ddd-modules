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
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Message;
use CloudCreativity\Modules\Toolkit\Loggable\ObjectDecorator;
use CloudCreativity\Modules\Toolkit\Loggable\Sensitive;
use CloudCreativity\Modules\Toolkit\Loggable\SimpleContextFactory;
use PHPUnit\Framework\TestCase;

class ObjectDecoratorTest extends TestCase
{
    /**
     * @var SimpleContextFactory
     */
    private SimpleContextFactory $factory;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new SimpleContextFactory();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->factory);
    }

    /**
     * @return void
     */
    public function testItUsesObjectProperties(): void
    {
        $source = new class () implements Message {
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

        $decorator = new ObjectDecorator($source);

        $this->assertInstanceOf(ContextProvider::class, $decorator);
        $this->assertSame(array_keys($expected), $decorator->keys());
        $this->assertSame($expected, iterator_to_array($decorator));
        $this->assertSame($expected, $decorator->all());
        $this->assertSame($expected, $decorator->context());
        $this->assertSame($expected, $this->factory->make($source));
    }

    /**
     * @return void
     */
    public function testItExcludesSensitiveProperties(): void
    {
        $source = new class ('Hello', 'World') implements Message {
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

        $decorator = new ObjectDecorator($source);

        $this->assertSame(array_keys($expected), $decorator->keys());
        $this->assertSame($expected, $decorator->all());
        $this->assertSame($expected, $decorator->context());
        $this->assertSame($expected, $this->factory->make($source));
    }
}
