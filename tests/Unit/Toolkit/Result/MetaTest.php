<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Result;

use CloudCreativity\Modules\Toolkit\Result\Meta;
use PHPUnit\Framework\TestCase;

class MetaTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $meta = new Meta($values = [
            'foo' => 'bar',
            'baz' => 'bat',
            'foobar' => null,
        ]);

        $this->assertCount(3, $meta);
        $this->assertSame($values, $meta->all());
        $this->assertSame($values['baz'], $meta->get('baz'));
        $this->assertNull($meta->get('foobar', 'default'));
        $this->assertTrue($meta->exists('foobar'));
        $this->assertNull($meta->get('blah'));
        $this->assertFalse($meta->exists('blah'));
        $this->assertSame('default', $meta->get('blah', 'default'));
        $this->assertTrue($meta->isNotEmpty());
        $this->assertFalse($meta->isEmpty());
    }

    /**
     * @return void
     */
    public function testEmpty(): void
    {
        $meta = new Meta();

        $this->assertCount(0, $meta);
        $this->assertTrue($meta->isEmpty());
        $this->assertFalse($meta->isNotEmpty());
    }

    /**
     * @return void
     */
    public function testArrayAccess(): void
    {
        $meta = new Meta(['foo' => 'bar', 'baz' => 'bat', 'foobar' => null]);

        $this->assertSame('bat', $meta['baz']);
        $this->assertNull($meta['foobar']);
        $this->assertTrue(isset($meta['foobar']));
        $this->assertFalse(isset($meta['blah']));
    }

    /**
     * @return void
     */
    public function testOffsetUnset(): void
    {
        $meta = new Meta(['foo' => 'bar']);

        $this->expectException(\LogicException::class);
        unset($meta['foo']);
    }

    /**
     * @return void
     */
    public function testOffsetSet(): void
    {
        $meta = new Meta(['foo' => 'bar']);

        $this->expectException(\LogicException::class);
        $meta['foo'] = 'foobar';
    }

    /**
     * @return void
     */
    public function testPut(): void
    {
        $original = new Meta(['foo' => 'bar', 'baz' => 'bat']);
        $actual = $original->put('foo', 'foobar');

        $this->assertNotSame($original, $actual);
        $this->assertSame(['foo' => 'bar', 'baz' => 'bat'], $original->all());
        $this->assertSame(['foo' => 'foobar', 'baz' => 'bat'], $actual->all());
    }

    /**
     * @return void
     */
    public function testMergeArray(): void
    {
        $original = new Meta(['foo' => 'bar', 'baz' => 'bat']);
        $actual = $original->merge(['foo' => null, 'foobar' => 'bazbat']);

        $this->assertNotSame($original, $actual);
        $this->assertSame(['foo' => 'bar', 'baz' => 'bat'], $original->all());
        $this->assertSame(['foo' => null, 'baz' => 'bat', 'foobar' => 'bazbat'], $actual->all());
    }

    /**
     * @return void
     */
    public function testMergeMeta(): void
    {
        $original = new Meta(['foo' => 'bar', 'baz' => 'bat']);
        $actual = $original->merge(new Meta(['foo' => null, 'foobar' => 'bazbat']));

        $this->assertNotSame($original, $actual);
        $this->assertSame(['foo' => 'bar', 'baz' => 'bat'], $original->all());
        $this->assertSame(['foo' => null, 'baz' => 'bat', 'foobar' => 'bazbat'], $actual->all());
    }
}
