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
use CloudCreativity\Modules\Tests\TestBackedEnum;
use CloudCreativity\Modules\Toolkit\ContractException;
use CloudCreativity\Modules\Toolkit\Result\Error;
use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $error = new Error(key: 'foo', message: 'Bar', code: TestBackedEnum::Foo);

        $this->assertInstanceOf(IError::class, $error);
        $this->assertSame('foo', $error->key());
        $this->assertSame('Bar', $error->message());
        $this->assertSame(TestBackedEnum::Foo, $error->code());
        $this->assertTrue($error->is(TestBackedEnum::Foo));
        $this->assertFalse($error->is(TestBackedEnum::Bar));
    }

    /**
     * @return void
     */
    public function testOnlyMessage(): void
    {
        $error = new Error(message: 'Hello World');

        $this->assertNull($error->key());
        $this->assertSame('Hello World', $error->message());
        $this->assertNull($error->code());
    }

    /**
     * @return void
     */
    public function testOnlyCode(): void
    {
        $error = new Error(code: TestBackedEnum::Foo);

        $this->assertNull($error->key());
        $this->assertSame('', $error->message());
        $this->assertSame(TestBackedEnum::Foo, $error->code());
    }

    /**
     * @return void
     */
    public function testNoMessageOrCode(): void
    {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Error must have a message or a code.');

        new Error(key: 'foo');
    }
}
