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

namespace CloudCreativity\Modules\Tests\Unit\Bus\Results;

use CloudCreativity\Modules\Bus\Results\Error;
use CloudCreativity\Modules\Bus\Results\ErrorInterface;
use CloudCreativity\Modules\Tests\Unit\Infrastructure\Log\TestEnum;
use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $error = new Error('foo', 'Bar', TestEnum::Foo);

        $this->assertInstanceOf(ErrorInterface::class, $error);
        $this->assertSame('foo', $error->key());
        $this->assertSame('Bar', $error->message());
        $this->assertSame(TestEnum::Foo, $error->code());
        $this->assertSame([
            'key' => 'foo',
            'message' => 'Bar',
            'code' => TestEnum::Foo->value,
        ], $error->context());
    }

    /**
     * @return void
     */
    public function testWithoutOptionalValues(): void
    {
        $error = new Error(null, 'Hello World');

        $this->assertNull($error->key());
        $this->assertSame('Hello World', $error->message());
        $this->assertNull($error->code());
        $this->assertSame([
            'message' => 'Hello World',
        ], $error->context());
    }
}
