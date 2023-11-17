<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Bus\Results;

use CloudCreativity\BalancedEvent\Common\Bus\Results\Error;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ErrorIterableInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ListOfErrors;
use CloudCreativity\BalancedEvent\Common\Bus\Results\Meta;
use CloudCreativity\BalancedEvent\Common\Bus\Results\Result;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ResultInterface;
use CloudCreativity\BalancedEvent\Common\Infrastructure\Log\ContextProviderInterface;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
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

    public function testFailedWithError(): void
    {
        $error = new Error(null, 'Something went wrong.');
        $result = Result::failed($error);

        $this->assertEquals(new ListOfErrors($error), $result->errors());
    }

    public function testFailedWithString(): void
    {
        $error = new Error(null, 'Something went wrong.');
        $result = Result::failed($error->message());

        $this->assertEquals(new ListOfErrors($error), $result->errors());
    }

    public function testFailedWithArray(): void
    {
        $error = new Error(null, 'Something went wrong.');
        $result = Result::failed([$error]);

        $this->assertEquals(new ListOfErrors($error), $result->errors());
    }

    public function testFailedWithoutErrors(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Result::failed(new ListOfErrors());
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

    public function testSuccessContext(): void
    {
        $result = Result::ok();

        $expected = [
            'success' => true,
        ];

        $this->assertEquals($expected, $result->context());
    }

    public function testSuccessContextWithMeta(): void
    {
        $object = $this->createMock(ContextProviderInterface::class);
        $object->method('context')->willReturn(['foo' => 'bar']);

        $result = Result::ok()->withMeta(['values' => $object]);

        $expected = [
            'success' => true,
            'meta' => [
                'values' => [
                    'foo' => 'bar',
                ],
            ],
        ];

        $this->assertEquals($expected, $result->context());
    }

    public function testFailureToArray(): void
    {
        $errors = $this->createMock(ErrorIterableInterface::class);
        $errors->method('context')->willReturn([['foo' => 'bar']]);

        $result = Result::failed($errors);

        $expected = [
            'success' => false,
            'errors' => [['foo' => 'bar']],
        ];

        $this->assertEquals($expected, $result->context());
    }

    public function testFailureToArrayWithMeta(): void
    {
        $errors = $this->createMock(ErrorIterableInterface::class);
        $errors->method('context')->willReturn([['foo' => 'bar']]);

        $object = $this->createMock(ContextProviderInterface::class);
        $object->method('context')->willReturn(['baz' => 'bat']);

        $result = Result::failed($errors)->withMeta(['values' => $object]);

        $expected = [
            'success' => false,
            'meta' => [
                'values' => [
                    'baz' => 'bat',
                ],
            ],
            'errors' => [['foo' => 'bar']],
        ];

        $this->assertEquals($expected, $result->context());
    }
}
