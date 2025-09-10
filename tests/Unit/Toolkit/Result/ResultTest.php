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

use CloudCreativity\Modules\Contracts\Toolkit\Result\ListOfErrors as IListOfErrors;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result as IResult;
use CloudCreativity\Modules\Tests\TestBackedEnum;
use CloudCreativity\Modules\Tests\TestUnitEnum;
use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\FailedResultException;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;
use CloudCreativity\Modules\Toolkit\Result\Meta;
use CloudCreativity\Modules\Toolkit\Result\Result;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
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
        $this->assertEquals($result, Result::fail($errors));

        return $result;
    }

    /**
     * @param Result<mixed> $result
     */
    #[Depends('testFailed')]
    public function testAbort(Result $result): void
    {
        try {
            $result->abort();
            $this->fail('No exception thrown.');
        } catch (FailedResultException $ex) {
            $this->assertSame($result, $ex->getResult());
        }
    }

    public function testErrorWithMultipleErrors(): void
    {
        $errors = new ListOfErrors(
            new Error(code: TestBackedEnum::Foo),
            new Error(code: TestUnitEnum::Baz),
            new Error(message: 'Message A'),
            new Error(message: 'Message B'),
        );

        $result = Result::failed($errors);

        $this->assertSame('Message A', $result->error());
    }

    /**
     * @param Result<null> $result
     */
    #[Depends('testFailed')]
    public function testItThrowsWhenGettingValueOnFailedResult(Result $result): void
    {
        try {
            $result->value();
            $this->fail('No exception thrown.');
        } catch (FailedResultException $ex) {
            $this->assertSame($result, $ex->getResult());
        }
    }

    public function testFailedWithListOfErrorsInterface(): void
    {
        $errors = $this->createMock(IListOfErrors::class);
        $errors->method('isNotEmpty')->willReturn(true);
        $result = Result::failed($errors);

        $this->assertFalse($result->didSucceed());
        $this->assertTrue($result->didFail());
        $this->assertSame($errors, $result->errors());
        $this->assertEquals($result, Result::fail($errors));
    }

    public function testFailedWithError(): void
    {
        $error = new Error(null, 'Something went wrong.');
        $result = Result::failed($error);

        $this->assertEquals(new ListOfErrors($error), $result->errors());
        $this->assertEquals($result, Result::fail($error));
    }

    public function testFailedWithString(): void
    {
        $error = new Error(null, 'Something went wrong.');
        $result = Result::failed($error->message());

        $this->assertEquals(new ListOfErrors($error), $result->errors());
        $this->assertEquals($result, Result::fail($error->message()));
    }

    public function testFailedWithArray(): void
    {
        $error = new Error(null, 'Something went wrong.');
        $result = Result::failed([$error]);

        $this->assertEquals(new ListOfErrors($error), $result->errors());
        $this->assertEquals($result, Result::fail([$error]));
    }

    public function testFailedWithBackedEnum(): void
    {
        $error = new Error(code: $code = TestBackedEnum::Foo);
        $result = Result::failed($code);

        $this->assertEquals(new ListOfErrors($error), $result->errors());
        $this->assertEquals($result, Result::fail($code));
    }

    public function testFailedWithoutErrors(): void
    {
        $this->expectException(\AssertionError::class);
        Result::failed(new ListOfErrors());
    }

    public function testFailWithoutErrors(): void
    {
        $this->expectException(\AssertionError::class);
        Result::fail(new ListOfErrors());
    }

    public function testWithMeta(): void
    {
        $meta = new Meta(['foo' => 'bar']);
        $result = Result::ok()->withMeta($meta);

        $this->assertEquals($meta, $result->meta());
    }

    public function testWithMetaArray(): void
    {
        $meta = new Meta(['foo' => 'bar']);
        $result = Result::ok()->withMeta($meta->all());

        $this->assertEquals($meta, $result->meta());
    }

    public function testWithMetaMergesValues(): void
    {
        $result1 = Result::ok()
            ->withMeta($values = ['foo' => 'bar', 'baz' => 'bat', 'foobar' => null]);

        $result2 = $result1->withMeta([
            'baz' => 'blah',
            'foobar' => 'bazbat',
            'bazbat' => 'blah!',
        ]);

        $this->assertSame($values, $result1->meta()->all());
        $this->assertSame([
            'foo' => 'bar',
            'baz' => 'blah',
            'foobar' => 'bazbat',
            'bazbat' => 'blah!',
        ], $result2->meta()->all());
    }
}
