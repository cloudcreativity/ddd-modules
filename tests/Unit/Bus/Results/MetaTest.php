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

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Bus\Results;

use CloudCreativity\BalancedEvent\Common\Bus\Results\Meta;
use CloudCreativity\BalancedEvent\Common\Infrastructure\Log\ContextProviderInterface;
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

    /**
     * @return void
     */
    public function testContext(): void
    {
        $serializable = $this->createMock(ContextProviderInterface::class);
        $serializable->method('context')->willReturn(['foo' => 'bar']);

        $meta = new Meta([
            'foo' => $serializable,
            'baz' => 'bat',
        ]);

        $this->assertInstanceOf(ContextProviderInterface::class, $meta);
        $this->assertSame([
            'foo' => ['foo' => 'bar'],
            'baz' => 'bat',
        ], $meta->context());
    }
}
