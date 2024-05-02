<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\Bus\Middleware;

use CloudCreativity\Modules\Application\Bus\Middleware\ValidateCommand;
use CloudCreativity\Modules\Contracts\Application\Bus\Validator;
use CloudCreativity\Modules\Contracts\Application\Messages\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;
use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidateCommandTest extends TestCase
{
    /**
     * @var Validator&MockObject
     */
    private Validator $validator;

    /**
     * @var ValidateCommand
     */
    private ValidateCommand $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = $this->createMock(Validator::class);

        $this->middleware = new class ($this->validator) extends ValidateCommand {
            /**
             * @return iterable<string>
             */
            protected function rules(): iterable
            {
                return ['foo', 'bar'];
            }
        };
    }

    /**
     * @return void
     */
    public function testItSucceeds(): void
    {
        $rules = [];
        $command = $this->createMock(Command::class);
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
            ->with($this->callback(function (Command $actual) use ($command, &$rules): bool {
                $this->assertSame(['foo', 'bar'], $rules);
                $this->assertSame($command, $actual);
                return true;
            }))
            ->willReturn(new ListOfErrors());

        $next = function ($actual) use ($command, $expected) {
            $this->assertSame($command, $actual);
            return $expected;
        };

        $actual = ($this->middleware)($command, $next);

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
            ->with($command = $this->createMock(Command::class))
            ->willReturn($errors = new ListOfErrors(new Error(null, 'Something went wrong.')));

        $next = function () {
            throw new \LogicException('Not expecting next closure to be called.');
        };

        $result = ($this->middleware)($command, $next);

        $this->assertTrue($result->didFail());
        $this->assertSame($errors, $result->errors());
    }
}
