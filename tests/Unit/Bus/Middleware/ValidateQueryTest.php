<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

namespace CloudCreativity\Modules\Tests\Unit\Bus\Middleware;

use CloudCreativity\Modules\Bus\Middleware\ValidateQuery;
use CloudCreativity\Modules\Bus\Validation\QueryValidatorInterface;
use CloudCreativity\Modules\Toolkit\Messages\QueryInterface;
use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidateQueryTest extends TestCase
{
    /**
     * @var QueryValidatorInterface&MockObject
     */
    private QueryValidatorInterface $validator;

    /**
     * @var ValidateQuery
     */
    private ValidateQuery $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = $this->createMock(QueryValidatorInterface::class);

        $this->middleware = new class ($this->validator) extends ValidateQuery {
            /**
             * @return iterable<string>
             */
            protected function rules(): iterable
            {
                return ['foobar', 'bazbat'];
            }
        };
    }

    /**
     * @return void
     */
    public function testItSucceeds(): void
    {
        $rules = [];
        $query = $this->createMock(QueryInterface::class);
        $expected = $this->createMock(ResultInterface::class);

        $this->validator
            ->expects($this->once())
            ->method('using')
            ->with($this->callback(function (array $actual) use (&$rules): bool {
                $rules = $actual;
                return true;
            }))
            ->willReturnSelf();

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($this->callback(function (QueryInterface $actual) use ($query, &$rules): bool {
                $this->assertSame(['foobar', 'bazbat'], $rules);
                $this->assertSame($query, $actual);
                return true;
            }))
            ->willReturn(new ListOfErrors());

        $next = function ($actual) use ($query, $expected) {
            $this->assertSame($query, $actual);
            return $expected;
        };

        $actual = ($this->middleware)($query, $next);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItFails(): void
    {
        $this->validator
            ->method('using')
            ->willReturnSelf();

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($query = $this->createMock(QueryInterface::class))
            ->willReturn($errors = new ListOfErrors(new Error(null, 'Something went wrong.')));

        $next = function () {
            throw new \LogicException('Not expecting next closure to be called.');
        };

        $result = ($this->middleware)($query, $next);

        $this->assertTrue($result->didFail());
        $this->assertSame($errors, $result->errors());
    }
}
