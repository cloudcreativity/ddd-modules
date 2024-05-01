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

use CloudCreativity\Modules\Contracts\Toolkit\Result\ListOfErrors as IListOfErrors;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result as IResult;
use CloudCreativity\Modules\Tests\Unit\Toolkit\Loggable\TestEnum;
use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\FailedResultException;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;
use CloudCreativity\Modules\Toolkit\Result\Meta;
use CloudCreativity\Modules\Toolkit\Result\Result;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    /**
     * @return void
     */
    public function testOk(): void
    {
        $result = Result::ok();
        $result->abort();

        $this->assertInstanceOf(IResult::class, $result);
        $this->assertNull($result->value());
        $this->assertNull($result->safe());
        $this->assertTrue($result->didSucceed());
        $this->assertFalse($result->didFail());
        $this->assertTrue($result->errors()->isEmpty());
        $this->assertTrue($result->meta()->isEmpty());
        $this->assertNull($result->error());
    }

    /**
     * @return void
     */
    public function testOkWithValue(): void
    {
        $result = Result::ok($value = 99);

        $this->assertSame($value, $result->value());
        $this->assertSame($value, $result->safe());
        $this->assertTrue($result->didSucceed());
        $this->assertFalse($result->didFail());
        $this->assertTrue($result->errors()->isEmpty());
        $this->assertTrue($result->meta()->isEmpty());
        $this->assertNull($result->error());
    }

    /**
     * @return Result<null>
     */
    public function testFailed(): Result
    {
        $errors = new ListOfErrors(new Error(null, 'Something went wrong.'));
        $result = Result::failed($errors);

        $this->assertFalse($result->didSucceed());
        $this->assertTrue($result->didFail());
        $this->assertNull($result->safe());
        $this->assertSame($errors, $result->errors());
        $this->assertTrue($result->meta()->isEmpty());
        $this->assertSame('Something went wrong.', $result->error());

        return $result;
    }

    /**
     * @param Result<mixed> $result
     * @return void
     * @depends testFailed
     */
    public function testAbort(Result $result): void
    {
        try {
            $result->abort();
            $this->fail('No exception thrown.');
        } catch (FailedResultException $ex) {
            $this->assertSame($result, $ex->getResult());
        }
    }

    /**
     * @return void
     */
    public function testErrorWithMultipleErrors(): void
    {
        $errors = new ListOfErrors(
            new Error(code: TestEnum::Foo),
            new Error(code: TestEnum::Bar),
            new Error(message: 'Message A'),
            new Error(message: 'Message B'),
        );

        $result = Result::failed($errors);

        $this->assertSame('Message A', $result->error());
    }

    /**
     * @param Result<null> $result
     * @return void
     * @depends testFailed
     */
    public function testItThrowsWhenGettingValueOnFailedResult(Result $result): void
    {
        try {
            $result->value();
            $this->fail('No exception thrown.');
        } catch (FailedResultException $ex) {
            $this->assertSame($result, $ex->getResult());
        }
    }

    /**
     * @return void
     */
    public function testFailedWithListOfErrorsInterface(): void
    {
        $errors = $this->createMock(IListOfErrors::class);
        $errors->method('isNotEmpty')->willReturn(true);
        $result = Result::failed($errors);

        $this->assertFalse($result->didSucceed());
        $this->assertTrue($result->didFail());
        $this->assertSame($errors, $result->errors());
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
    public function testFailedWithBackedEnum(): void
    {
        $error = new Error(code: $code = TestEnum::Foo);
        $result = Result::failed($code);

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
