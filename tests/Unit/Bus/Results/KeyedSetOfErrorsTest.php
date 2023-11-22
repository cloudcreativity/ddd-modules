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

namespace CloudCreativity\Modules\Tests\Unit\Bus\Results;

use CloudCreativity\Modules\Bus\Results\Error;
use CloudCreativity\Modules\Bus\Results\KeyedSetOfErrors;
use CloudCreativity\Modules\Bus\Results\ListOfErrors;
use PHPUnit\Framework\TestCase;

class KeyedSetOfErrorsTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $errors = new KeyedSetOfErrors(
            $a = new Error('foo', 'Message A'),
            $b = new Error('bar', 'Message B'),
            $c = new Error('foo', 'Message C'),
            $d = new Error(null, 'Message D'),
            $e = new Error(null, 'Message E'),
        );

        $expected = [
            '_base' => new ListOfErrors($d, $e),
            'bar' => new ListOfErrors($b),
            'foo' => new ListOfErrors($a, $c),
        ];

        $this->assertEquals($expected, iterator_to_array($errors));
        $this->assertEquals($expected, $errors->all());
        $this->assertSame(['_base', 'bar', 'foo'], $errors->keys());
        $this->assertEquals(new ListOfErrors($d, $e, $b, $a, $c), $errors->toList());
        $this->assertCount(5, $errors);
        $this->assertTrue($errors->isNotEmpty());
        $this->assertFalse($errors->isEmpty());
    }

    /**
     * @return void
     */
    public function testEmpty(): void
    {
        $errors = new KeyedSetOfErrors();

        $this->assertTrue($errors->isEmpty());
        $this->assertFalse($errors->isNotEmpty());
        $this->assertCount(0, $errors);
    }

    /**
     * @return void
     */
    public function testPutNewKey(): void
    {
        $original = new KeyedSetOfErrors(
            $a = new Error('foo', 'Message A'),
            $b = new Error('bar', 'Message B'),
            $c = new Error('foo', 'Message C'),
        );

        $actual = $original->put($d = new Error('baz', 'Message D'));

        $this->assertNotSame($original, $actual);
        $this->assertEquals([
            'foo' => new ListOfErrors($a, $c),
            'bar' => new ListOfErrors($b),
        ], iterator_to_array($original));
        $this->assertEquals([
            'foo' => new ListOfErrors($a, $c),
            'bar' => new ListOfErrors($b),
            'baz' => new ListOfErrors($d),
        ], iterator_to_array($actual));
        $this->assertSame(['bar', 'baz', 'foo'], $actual->keys());
    }

    /**
     * @return void
     */
    public function testPutExistingKey(): void
    {
        $original = new KeyedSetOfErrors(
            $a = new Error('foo', 'Message A'),
            $b = new Error('bar', 'Message B'),
            $c = new Error('foo', 'Message C'),
        );

        $actual = $original->put($d = new Error('bar', 'Message D'));

        $this->assertNotSame($original, $actual);
        $this->assertEquals([
            'foo' => new ListOfErrors($a, $c),
            'bar' => new ListOfErrors($b),
        ], iterator_to_array($original));
        $this->assertEquals([
            'foo' => new ListOfErrors($a, $c),
            'bar' => new ListOfErrors($b, $d),
        ], iterator_to_array($actual));
        $this->assertSame(['bar', 'foo'], $actual->keys());
    }

    /**
     * @return void
     */
    public function testPutErrorWithoutKey1(): void
    {
        $original = new KeyedSetOfErrors(
            $a = new Error('foo', 'Message A'),
            $b = new Error('bar', 'Message B'),
            $c = new Error('foo', 'Message C'),
        );

        $actual = $original->put($d = new Error(null, 'Message D'));

        $this->assertNotSame($original, $actual);
        $this->assertEquals([
            'foo' => new ListOfErrors($a, $c),
            'bar' => new ListOfErrors($b),
        ], iterator_to_array($original));
        $this->assertEquals([
            '_base' => new ListOfErrors($d),
            'foo' => new ListOfErrors($a, $c),
            'bar' => new ListOfErrors($b),
        ], iterator_to_array($actual));
        $this->assertSame(['_base', 'bar', 'foo'], $actual->keys());
    }

    /**
     * @return void
     */
    public function testPutErrorWithoutKey2(): void
    {
        $original = new KeyedSetOfErrors(
            $a = new Error(null, 'Message A'),
            $b = new Error('foo', 'Message B'),
            $c = new Error(null, 'Message C'),
        );

        $actual = $original->put($d = new Error(null, 'Message D'));

        $this->assertNotSame($original, $actual);
        $this->assertEquals([
            '_base' => new ListOfErrors($a, $c),
            'foo' => new ListOfErrors($b),
        ], iterator_to_array($original));
        $this->assertEquals([
            '_base' => new ListOfErrors($a, $c, $d),
            'foo' => new ListOfErrors($b),
        ], iterator_to_array($actual));
        $this->assertSame(['_base', 'foo'], $actual->keys());
    }

    /**
     * @return void
     */
    public function testMerge(): void
    {
        $set1 = new KeyedSetOfErrors(
            $a = new Error('foo', 'Message A'),
            $b = new Error('bar', 'Message B'),
            $c = new Error('foo', 'Message C'),
        );

        $set2 = new KeyedSetOfErrors(
            $d = new Error('bar', 'Message D'),
            $e = new Error('baz', 'Message E'),
            $f = new Error('bar', 'Message F'),
            $g = new Error(null, 'Message G'),
        );

        $actual = $set1->merge($set2);

        $this->assertNotSame($set1, $actual);
        $this->assertNotSame($set2, $actual);

        $this->assertEquals([
            'bar' => new ListOfErrors($b),
            'foo' => new ListOfErrors($a, $c),
        ], iterator_to_array($set1));

        $this->assertEquals([
            '_base' => new ListOfErrors($g),
            'bar' => new ListOfErrors($d, $f),
            'baz' => new ListOfErrors($e),
        ], iterator_to_array($set2));

        $this->assertEquals([
            '_base' => new ListOfErrors($g),
            'bar' => new ListOfErrors($b, $d, $f),
            'baz' => new ListOfErrors($e),
            'foo' => new ListOfErrors($a, $c),
        ], iterator_to_array($actual));
        $this->assertSame(['_base', 'bar', 'baz', 'foo'], $actual->keys());
        $this->assertCount($set1->count() + $set2->count(), $actual);
    }

    /**
     * @return void
     */
    public function testContext(): void
    {
        $errors = new KeyedSetOfErrors(
            $a = new Error('foo', 'Message A'),
            $b = new Error('bar', 'Message B'),
            $c = new Error('foo', 'Message C'),
            $d = new Error(null, 'Message D'),
            $e = new Error(null, 'Message E'),
        );

        $expected = [
            '_base' => (new ListOfErrors($d, $e))->context(),
            'bar' => (new ListOfErrors($b))->context(),
            'foo' => (new ListOfErrors($a, $c))->context(),
        ];

        $this->assertSame($expected, $errors->context());
    }
}
