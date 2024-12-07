<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Loggable;

use BackedEnum;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Contracts\Toolkit\Loggable\ContextProvider;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Error as IError;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result as IResult;
use CloudCreativity\Modules\Toolkit\Loggable\ResultContext;
use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @extends IResult<null>
 */
interface ResultWithContext extends IResult, ContextProvider
{
}

interface ErrorWithContext extends IError, ContextProvider
{
}

class ResultContextTest extends TestCase
{
    /**
     * @return void
     */
    public function testSuccess(): void
    {
        $result = Result::ok();

        $expected = [
            'success' => true,
        ];

        $this->assertSame($expected, ResultContext::from($result)->context());
    }

    /**
     * @return void
     */
    public function testSuccessWithContextProvider(): void
    {
        $expected = [
            'success' => true,
            'value' => [
                'foo' => 'bar',
                'blah!' => 'blah!!',
            ],
        ];

        $value = $this->createMock(ContextProvider::class);
        $value->method('context')->willReturn($expected['value']);

        $result = Result::ok($value);

        $this->assertSame($expected, ResultContext::from($result)->context());
    }

    /**
     * @return void
     */
    public function testSuccessWithIdentifier(): void
    {
        $expected = [
            'success' => true,
            'value' => 99,
        ];

        $value = $this->createMock(Identifier::class);
        $value->method('context')->willReturn($expected['value']);

        $result = Result::ok($value);

        $this->assertSame($expected, ResultContext::from($result)->context());
    }

    /**
     * @return array<array<scalar>>
     */
    public static function scalarProvider(): array
    {
        return [
            [true],
            [false],
            [1],
            [1.1],
            ['foo'],
        ];
    }

    /**
     * @param mixed $value
     * @return void
     * @dataProvider scalarProvider
     */
    public function testSuccessWithScalarOrNull(mixed $value): void
    {
        $expected = [
            'success' => true,
            'value' => $value,
        ];

        $result = Result::ok($value);

        $this->assertSame($expected, ResultContext::from($result)->context());
    }

    /**
     * @return void
     */
    public function testSuccessContextWithMeta(): void
    {
        $result = Result::ok()->withMeta(['foo' => 'bar']);

        $expected = [
            'success' => true,
            'meta' => [
                'foo' => 'bar',
            ],
        ];

        $this->assertSame($expected, ResultContext::from($result)->context());
    }

    /**
     * @return array<array<string|Error>>
     */
    public static function onlyMessageProvider(): array
    {
        return [
            ['Something went wrong.'],
            [new Error(message: 'Something went wrong.')],
        ];
    }

    /**
     * @param string|Error $error
     * @return void
     * @dataProvider onlyMessageProvider
     */
    public function testFailureContextWithErrorThatOnlyHasMessage(string|Error $error): void
    {
        $result = Result::failed($error);

        $expected = [
            'success' => false,
            'error' => is_string($error) ? $error : $error->message(),
        ];

        $this->assertSame($expected, ResultContext::from($result)->context());
    }

    /**
     * @return array<array<BackedEnum|Error>>
     */
    public static function onlyCodeProvider(): array
    {
        return [
            [TestEnum::Foo],
            [new Error(code: TestEnum::Bar)],
        ];
    }

    /**
     * @param BackedEnum|Error $error
     * @return void
     * @dataProvider onlyCodeProvider
     */
    public function testFailureContextWithErrorThatOnlyHasCode(BackedEnum|Error $error): void
    {
        $result = Result::failed($error);

        $expected = [
            'success' => false,
            'error' => $error instanceof BackedEnum ? $error->value : $error->code()?->value,
        ];

        $this->assertSame($expected, ResultContext::from($result)->context());
    }

    /**
     * @return array<array<int, mixed>>
     */
    public static function errorsProvider(): array
    {
        $error1 = new Error(
            key: 'foo',
            message: 'Something went wrong.',
            code: TestEnum::Bar,
        );

        $error2 = new Error(
            key: 'bar',
            message: 'Something else went wrong.',
            code: TestEnum::Foo,
        );

        $expected1 = [
            'code' => TestEnum::Bar->value,
            'key' => 'foo',
            'message' => 'Something went wrong.',
        ];

        $expected2 = [
            'code' => TestEnum::Foo->value,
            'key' => 'bar',
            'message' => 'Something else went wrong.',
        ];

        return [
            [[$error1], [$expected1]],
            [[$error1, $error2], [$expected1, $expected2]],
        ];
    }

    /**
     * @param array<Error> $errors
     * @param array<int, array<string, mixed>> $expected
     * @return void
     * @dataProvider errorsProvider
     */
    public function testFailureContextWithMeta(array $errors, array $expected): void
    {
        $result = Result::failed($errors)->withMeta(['baz' => 'bat']);

        $expected = [
            'success' => false,
            'errors' => $expected,
            'meta' => [
                'baz' => 'bat',
            ],
        ];

        $this->assertSame($expected, ResultContext::from($result)->context());
    }

    /**
     * @return void
     */
    public function testItHasLogContext(): void
    {
        $mock = $this->createMock(ResultWithContext::class);
        $mock->method('context')->willReturn($expected = ['foo' => 'bar', 'baz' => 'bat']);

        $this->assertSame($expected, ResultContext::from($mock)->context());
    }

    /**
     * @return void
     */
    public function testItHasErrorWithLogContext(): void
    {
        $mock = $this->createMock(ErrorWithContext::class);
        $mock->method('context')->willReturn($expected = ['foo' => 'bar', 'baz' => 'bat']);

        $error = new Error(null, 'Something went wrong.');

        $result = Result::failed([$mock, $error]);

        $expected = [
            'success' => false,
            'errors' => [
                $expected,
                [
                    'message' => 'Something went wrong.',
                ],
            ],
        ];

        $this->assertSame($expected, ResultContext::from($result)->context());
    }
}
