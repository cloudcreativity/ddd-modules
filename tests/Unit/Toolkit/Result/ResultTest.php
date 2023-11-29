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

use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;
use CloudCreativity\Modules\Toolkit\Result\Meta;
use CloudCreativity\Modules\Toolkit\Result\Result;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    /**
     * @return void
     */
    public function testOk(): void
    {
        $result = Result::ok();

        $this->assertInstanceOf(ResultInterface::class, $result);
        $this->assertTrue($result->didSucceed());
        $this->assertFalse($result->didFail());
        $this->assertTrue($result->errors()->isEmpty());
        $this->assertTrue($result->meta()->isEmpty());
        $this->assertNull($result->error());
    }

    /**
     * @return void
     */
    public function testFailed(): void
    {
        $errors = new ListOfErrors(new Error(null, 'Something went wrong.'));
        $result = Result::failed($errors);

        $this->assertFalse($result->didSucceed());
        $this->assertTrue($result->didFail());
        $this->assertSame($errors, $result->errors());
        $this->assertTrue($result->meta()->isEmpty());
        $this->assertSame('Something went wrong.', $result->error());
    }

    /**
     * @return void
     */
    public function testFailedWithError(): void
    {
        $error = new Error(null, 'Something went wrong.');
        $result = Result::failed($error);

        $this->assertEquals(new ListOfErrors($error), $result->errors());
    }

    /**
     * @return void
     */
    public function testFailedWithString(): void
    {
        $error = new Error(null, 'Something went wrong.');
        $result = Result::failed($error->message());

        $this->assertEquals(new ListOfErrors($error), $result->errors());
    }

    /**
     * @return void
     */
    public function testFailedWithArray(): void
    {
        $error = new Error(null, 'Something went wrong.');
        $result = Result::failed([$error]);

        $this->assertEquals(new ListOfErrors($error), $result->errors());
    }

    /**
     * @return void
     */
    public function testFailedWithoutErrors(): void
    {
        $this->expectException(\AssertionError::class);
        Result::failed(new ListOfErrors());
    }

    /**
     * @return void
     */
    public function testWithMeta(): void
    {
        $meta = new Meta(['foo' => 'bar']);
        $result = Result::ok()->withMeta($meta);

        $this->assertEquals($meta, $result->meta());
    }

    /**
     * @return void
     */
    public function testWithMetaArray(): void
    {
        $meta = new Meta(['foo' => 'bar']);
        $result = Result::ok()->withMeta($meta->all());

        $this->assertEquals($meta, $result->meta());
    }

    /**
     * @return void
     */
    public function testWithMetaMergesValues(): void
    {
        $result1 = Result::ok()
            ->withMeta($values = ['foo' => 'bar', 'baz' => 'bat']);

        $result2 = $result1->withMeta([
            'baz' => 'blah',
            'foobar' => 'bazbat',
        ]);

        $this->assertSame($values, $result1->meta()->all());
        $this->assertSame([
            'foo' => 'bar',
            'baz' => 'blah',
            'foobar' => 'bazbat',
        ], $result2->meta()->all());
    }
}
