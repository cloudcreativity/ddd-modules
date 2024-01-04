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
use CloudCreativity\Modules\Toolkit\ContractException;
use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\ErrorInterface;
use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $error = new Error(key: 'foo', message: 'Bar', code: TestEnum::Foo);

        $this->assertInstanceOf(ErrorInterface::class, $error);
        $this->assertSame('foo', $error->key());
        $this->assertSame('Bar', $error->message());
        $this->assertSame(TestEnum::Foo, $error->code());
        $this->assertTrue($error->is(TestEnum::Foo));
        $this->assertFalse($error->is(TestEnum::Bar));
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
        $error = new Error(code: TestEnum::Foo);

        $this->assertNull($error->key());
        $this->assertSame('', $error->message());
        $this->assertSame(TestEnum::Foo, $error->code());
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
