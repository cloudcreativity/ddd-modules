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

use CloudCreativity\Modules\Contracts\Toolkit\Result\Error as IError;
use CloudCreativity\Modules\Contracts\Toolkit\Result\ListOfErrors as IListOfErrors;
use CloudCreativity\Modules\Tests\TestBackedEnum;
use CloudCreativity\Modules\Tests\TestUnitEnum;
use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\KeyedSetOfErrors;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;
use PHPUnit\Framework\TestCase;

class ListOfErrorsTest extends TestCase
{
    public function test(): void
    {
        $errors = new ListOfErrors(
            $a = new Error(null, 'Message A'),
            $b = new Error(null, 'Message B'),
        );

        $this->assertInstanceOf(IListOfErrors::class, $errors);
        $this->assertSame([$a, $b], iterator_to_array($errors));
        $this->assertSame([$a, $b], $errors->all());
        $this->assertEquals(new KeyedSetOfErrors($a, $b), $errors->toKeyedSet());
        $this->assertCount(2, $errors);
        $this->assertTrue($errors->isNotEmpty());
        $this->assertFalse($errors->isEmpty());
    }

    public function testEmpty(): void
    {
        $errors = new ListOfErrors();

        $this->assertTrue($errors->isEmpty());
        $this->assertFalse($errors->isNotEmpty());
        $this->assertSame(0, $errors->count());
        $this->assertNull($errors->first());
        $this->assertEmpty($errors->codes());
    }

    public function testPush(): void
    {
        $original = new ListOfErrors(
            $a = new Error(null, 'Message A'),
            $b = new Error(null, 'Message B'),
        );

        $actual = $original->push($c = new Error(null, 'Message C'));

        $this->assertNotSame($original, $actual);
        $this->assertSame([$a, $b], $original->all());
        $this->assertSame([$a, $b, $c], $actual->all());
    }

    public function testMerge(): void
    {
        $stack1 = new ListOfErrors(
            $a = new Error(null, 'Message A'),
            $b = new Error(null, 'Message B'),
        );

        $stack2 = new ListOfErrors(
            $c = new Error(null, 'Message C'),
            $d = new Error(null, 'Message D'),
        );

        $actual = $stack1->merge($stack2);

        $this->assertNotSame($stack1, $actual);
        $this->assertNotSame($stack2, $actual);
        $this->assertSame([$a, $b], $stack1->all());
        $this->assertSame([$c, $d], $stack2->all());
        $this->assertSame([$a, $b, $c, $d], $actual->all());
    }

    public function testFirst(): void
    {
        $errors = new ListOfErrors(
            $a = new Error(null, 'Message A'),
            new Error(null, 'Message B'),
            $c = new Error(null, 'Message C'),
            new Error(null, 'Message D'),
            $e = new Error(code: TestUnitEnum::Bat),
        );

        $this->assertSame($a, $errors->first());
        $this->assertSame($c, $errors->first(fn (IError $error) => 'Message C' === $error->message()));
        $this->assertSame($e, $errors->first(TestUnitEnum::Bat));
        $this->assertNull($errors->first(fn (IError $error) => 'Message E' === $error->message()));
        $this->assertNull($errors->first(TestUnitEnum::Baz));
    }

    public function testContains(): void
    {
        $errors = new ListOfErrors(
            new Error(message: 'Message A'),
            new Error(message: 'Message B'),
            new Error(message: 'Message C'),
            new Error(message: 'Message D', code: TestUnitEnum::Baz),
        );

        $this->assertTrue($errors->contains(fn (IError $error) => 'Message C' === $error->message()));
        $this->assertTrue($errors->contains(TestUnitEnum::Baz));
        $this->assertFalse($errors->contains(fn (IError $error) => 'Message E' === $error->message()));
        $this->assertFalse($errors->contains(TestUnitEnum::Bat));
    }

    public function testCodes(): void
    {
        $errors1 = new ListOfErrors(
            new Error(message: 'Message A'),
            new Error(message: 'Message B', code: TestBackedEnum::Foo),
            new Error(message: 'Message C', code: TestUnitEnum::Baz),
            new Error(message: 'Message D', code: TestBackedEnum::Foo),
        );

        $errors2 = new ListOfErrors(
            new Error(message: 'Message E'),
            new Error(message: 'Message F'),
        );

        $this->assertSame([TestBackedEnum::Foo, TestUnitEnum::Baz], $errors1->codes());
        $this->assertEmpty($errors2->codes());
    }

    public function testCode(): void
    {
        $errors1 = new ListOfErrors(
            new Error(message: 'Message A'),
            new Error(message: 'Message B', code: TestBackedEnum::Foo),
            new Error(message: 'Message C', code: TestUnitEnum::Baz),
            new Error(message: 'Message D', code: TestBackedEnum::Foo),
        );

        $errors2 = new ListOfErrors(
            new Error(message: 'Message E'),
            new Error(message: 'Message F'),
        );

        $this->assertSame(TestBackedEnum::Foo, $errors1->code());
        $this->assertNull($errors2->code());
    }
}
