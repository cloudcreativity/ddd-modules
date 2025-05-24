<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Result;

use CloudCreativity\Modules\Tests\TestUnitEnum;
use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\KeyedSetOfErrors;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;
use PHPUnit\Framework\TestCase;

class KeyedSetOfErrorsTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $errors = new KeyedSetOfErrors(
            $a = new Error(message: 'Message A', key: 'foo'),
            $b = new Error(message: 'Message B', key: 'bar'),
            $c = new Error(message: 'Message C', key: 'foo'),
            $d = new Error(message: 'Message D'),
            $e = new Error(message: 'Message E'),
            $f = new Error(message: 'Message F', key: TestUnitEnum::Baz),
            $g = new Error(message: 'Message G', key: TestUnitEnum::Bat),
            $h = new Error(message: 'Message H', key: TestUnitEnum::Baz),
        );

        $expected = [
            '_base' => new ListOfErrors($d, $e),
            'bar' => new ListOfErrors($b),
            TestUnitEnum::Bat->name => new ListOfErrors($g),
            TestUnitEnum::Baz->name => new ListOfErrors($f, $h),
            'foo' => new ListOfErrors($a, $c),
        ];

        $this->assertEquals($expected, iterator_to_array($errors));
        $this->assertEquals($expected, $errors->all());
        $this->assertSame(['_base', 'bar', TestUnitEnum::Bat->name, TestUnitEnum::Baz->name, 'foo'], $errors->keys());
        $this->assertEquals(new ListOfErrors($d, $e, $b, $g, $f, $h, $a, $c), $errors->toList());
        $this->assertCount(8, $errors);
        $this->assertTrue($errors->isNotEmpty());
        $this->assertFalse($errors->isEmpty());
        $this->assertEquals($expected['bar'], $errors->get('bar'));
        $this->assertEquals($expected[TestUnitEnum::Baz->name], $errors->get(TestUnitEnum::Baz));
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
            $a = new Error(message: 'Message A', key: 'foo'),
            $b = new Error(message: 'Message B', key: 'bar'),
            $c = new Error(message: 'Message C', key: 'foo'),
        );

        $actual = $original->put($d = new Error(message: 'Message D', key: 'baz'));

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
            $a = new Error(message: 'Message A', key: 'foo'),
            $b = new Error(message: 'Message B', key: 'bar'),
            $c = new Error(message: 'Message C', key: 'foo'),
        );

        $actual = $original->put($d = new Error(message: 'Message D', key: 'bar'));

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
            $a = new Error(message: 'Message A', key: 'foo'),
            $b = new Error(message: 'Message B', key: 'bar'),
            $c = new Error(message: 'Message C', key: 'foo'),
        );

        $actual = $original->put($d = new Error(message: 'Message D'));

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
            $a = new Error(message: 'Message A'),
            $b = new Error(message: 'Message B', key: 'foo'),
            $c = new Error(message: 'Message C'),
        );

        $actual = $original->put($d = new Error(message: 'Message D'));

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
            $a = new Error(message: 'Message A', key: 'foo'),
            $b = new Error(message: 'Message B', key: 'bar'),
            $c = new Error(message: 'Message C', key: 'foo'),
        );

        $set2 = new KeyedSetOfErrors(
            $d = new Error(message: 'Message D', key: 'bar'),
            $e = new Error(message: 'Message E', key: 'baz'),
            $f = new Error(message: 'Message F', key: 'bar'),
            $g = new Error(message: 'Message G'),
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
}
