<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Loggable;

use BackedEnum;
use CloudCreativity\Modules\Contracts\Toolkit\Loggable\ContextProvider;
use CloudCreativity\Modules\Contracts\Toolkit\Loggable\Contextual;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Error as IError;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result as IResult;
use CloudCreativity\Modules\Tests\TestBackedEnum;
use CloudCreativity\Modules\Tests\TestBackedIntEnum;
use CloudCreativity\Modules\Tests\TestUnitEnum;
use CloudCreativity\Modules\Toolkit\Loggable\ResultDecorator;
use CloudCreativity\Modules\Toolkit\Loggable\SimpleContextFactory;
use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\Result;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnitEnum;

use function CloudCreativity\Modules\Toolkit\enum_string;

/**
 * @extends IResult<null>
 */
interface ResultWithContext extends IResult, ContextProvider
{
}

interface ErrorWithContext extends IError, ContextProvider
{
}

class ResultDecoratorTest extends TestCase
{
    private SimpleContextFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new SimpleContextFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->factory);
    }

    public function testSuccess(): void
    {
        $result = Result::ok();

        $expected = [
            'success' => true,
        ];

        $decorator = new ResultDecorator($result);

        $this->assertInstanceOf(ContextProvider::class, $decorator);
        $this->assertSame($expected, $decorator->context());
        $this->assertSame($expected, $this->factory->make($result));
    }

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

        $this->assertSame($expected, (new ResultDecorator($result))->context());
        $this->assertSame($expected, $this->factory->make($result));
    }

    public function testSuccessWithIdentifier(): void
    {
        $expected = [
            'success' => true,
            'value' => 99,
        ];

        $value = $this->createMock(Contextual::class);
        $value->method('context')->willReturn($expected['value']);

        $result = Result::ok($value);

        $this->assertSame($expected, (new ResultDecorator($result))->context());
        $this->assertSame($expected, $this->factory->make($result));
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

    #[DataProvider('scalarProvider')]
    public function testSuccessWithScalarOrNull(mixed $value): void
    {
        $expected = [
            'success' => true,
            'value' => $value,
        ];

        $result = Result::ok($value);

        $this->assertSame($expected, (new ResultDecorator($result))->context());
        $this->assertSame($expected, $this->factory->make($result));
    }

    public function testSuccessContextWithMeta(): void
    {
        $result = Result::ok()->withMeta(['foo' => 'bar']);

        $expected = [
            'success' => true,
            'meta' => [
                'foo' => 'bar',
            ],
        ];

        $this->assertSame($expected, (new ResultDecorator($result))->context());
        $this->assertSame($expected, $this->factory->make($result));
    }

    /**
     * @return array<array<Error|string>>
     */
    public static function onlyMessageProvider(): array
    {
        return [
            ['Something went wrong.'],
            [new Error(message: 'Something went wrong.')],
        ];
    }

    #[DataProvider('onlyMessageProvider')]
    public function testFailureContextWithErrorThatOnlyHasMessage(Error|string $error): void
    {
        $result = Result::failed($error);

        $expected = [
            'success' => false,
            'error' => is_string($error) ? $error : $error->message(),
        ];

        $this->assertSame($expected, (new ResultDecorator($result))->context());
        $this->assertSame($expected, $this->factory->make($result));
    }

    /**
     * @return array<array<Error|UnitEnum>>
     */
    public static function onlyCodeProvider(): array
    {
        return [
            [TestUnitEnum::Baz],
            [new Error(code: TestBackedEnum::Bar)],
        ];
    }

    /**
     * @param BackedEnum|Error $error
     */
    #[DataProvider('onlyCodeProvider')]
    public function testFailureContextWithErrorThatOnlyHasCode(Error|UnitEnum $error): void
    {
        $result = Result::failed($error);
        $code = $error instanceof UnitEnum ? $error : $error->code();

        $expected = [
            'success' => false,
            'error' => enum_string($code ?? '!!'),
        ];

        $this->assertSame($expected, (new ResultDecorator($result))->context());
        $this->assertSame($expected, $this->factory->make($result));
    }

    /**
     * @return array<array<int, mixed>>
     */
    public static function errorsProvider(): array
    {
        $error1 = new Error(
            code: TestBackedEnum::Bar,
            message: 'Something went wrong.',
            key: 'foo',
        );

        $error2 = new Error(
            code: TestBackedIntEnum::FooBar,
            message: 'Something else went wrong.',
            key: TestBackedIntEnum::BazBat,
        );

        $expected1 = [
            'code' => TestBackedEnum::Bar->value,
            'key' => 'foo',
            'message' => 'Something went wrong.',
        ];

        $expected2 = [
            'code' => TestBackedIntEnum::FooBar->name,
            'key' => TestBackedIntEnum::BazBat->name,
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
     */
    #[DataProvider('errorsProvider')]
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

        $this->assertSame($expected, (new ResultDecorator($result))->context());
        $this->assertSame($expected, $this->factory->make($result));
    }

    public function testItHasLogContext(): void
    {
        $mock = $this->createMock(ResultWithContext::class);
        $mock->method('context')->willReturn($expected = ['foo' => 'bar', 'baz' => 'bat']);

        $this->assertSame($expected, (new ResultDecorator($mock))->context());
        $this->assertSame($expected, $this->factory->make($mock));
    }

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

        $this->assertSame($expected, (new ResultDecorator($result))->context());
        $this->assertSame($expected, $this->factory->make($result));
    }
}
