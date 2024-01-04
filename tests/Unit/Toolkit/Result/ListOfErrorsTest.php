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

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Result;

use CloudCreativity\Modules\Tests\Unit\Infrastructure\Log\TestEnum;
use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\ErrorInterface;
use CloudCreativity\Modules\Toolkit\Result\KeyedSetOfErrors;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrorsInterface;
use PHPUnit\Framework\TestCase;

class ListOfErrorsTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $errors = new ListOfErrors(
            $a = new Error(null, 'Message A'),
            $b = new Error(null, 'Message B'),
        );

        $this->assertInstanceOf(ListOfErrorsInterface::class, $errors);
        $this->assertSame([$a, $b], iterator_to_array($errors));
        $this->assertSame([$a, $b], $errors->all());
        $this->assertEquals(new KeyedSetOfErrors($a, $b), $errors->toKeyedSet());
        $this->assertCount(2, $errors);
        $this->assertTrue($errors->isNotEmpty());
        $this->assertFalse($errors->isEmpty());
    }

    /**
     * @return void
     */
    public function testEmpty(): void
    {
        $errors = new ListOfErrors();

        $this->assertTrue($errors->isEmpty());
        $this->assertFalse($errors->isNotEmpty());
        $this->assertSame(0, $errors->count());
        $this->assertNull($errors->first());
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function testFirst(): void
    {
        $errors = new ListOfErrors(
            $a = new Error(null, 'Message A'),
            new Error(null, 'Message B'),
            $c = new Error(null, 'Message C'),
            new Error(null, 'Message D'),
            $e = new Error(code: TestEnum::Bar),
        );

        $this->assertSame($a, $errors->first());
        $this->assertSame($c, $errors->first(fn (ErrorInterface $error) => 'Message C' === $error->message()));
        $this->assertSame($e, $errors->first(TestEnum::Bar));
        $this->assertNull($errors->first(fn (ErrorInterface $error) => 'Message E' === $error->message()));
        $this->assertNull($errors->first(TestEnum::Foo));
    }

    /**
     * @return void
     */
    public function testContains(): void
    {
        $errors = new ListOfErrors(
            new Error(message: 'Message A'),
            new Error(message: 'Message B'),
            new Error(message: 'Message C'),
            new Error(message: 'Message D', code: TestEnum::Foo),
        );

        $this->assertTrue($errors->contains(fn (ErrorInterface $error) => 'Message C' === $error->message()));
        $this->assertTrue($errors->contains(TestEnum::Foo));
        $this->assertFalse($errors->contains(fn (ErrorInterface $error) => 'Message E' === $error->message()));
        $this->assertFalse($errors->contains(TestEnum::Bar));
    }
}
