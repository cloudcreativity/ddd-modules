<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\Bus\Middleware;

use CloudCreativity\Modules\Application\Bus\Middleware\ValidateQuery;
use CloudCreativity\Modules\Contracts\Application\Bus\Validator;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;
use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidateQueryTest extends TestCase
{
    /**
     * @var Validator&MockObject
     */
    private Validator $validator;

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

        $this->validator = $this->createMock(Validator::class);

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
        $query = $this->createMock(Query::class);
        $expected = $this->createMock(Result::class);

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
            ->with($this->callback(function (Query $actual) use ($query, &$rules): bool {
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
            ->with($query = $this->createMock(Query::class))
            ->willReturn($errors = new ListOfErrors(new Error(null, 'Something went wrong.')));

        $next = function () {
            throw new \LogicException('Not expecting next closure to be called.');
        };

        $result = ($this->middleware)($query, $next);

        $this->assertTrue($result->didFail());
        $this->assertSame($errors, $result->errors());
    }
}
