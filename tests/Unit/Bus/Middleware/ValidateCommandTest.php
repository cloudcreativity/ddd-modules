<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Bus\Middleware;

use CloudCreativity\Modules\Bus\Middleware\ValidateCommand;
use CloudCreativity\Modules\Bus\Validation\CommandValidatorInterface;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidateCommandTest extends TestCase
{
    /**
     * @var CommandValidatorInterface&MockObject
     */
    private CommandValidatorInterface $validator;

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

        $this->validator = $this->createMock(CommandValidatorInterface::class);

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
        $command = $this->createMock(CommandInterface::class);
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
            ->with($this->callback(function (CommandInterface $actual) use ($command, &$rules): bool {
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
            ->with($command = $this->createMock(CommandInterface::class))
            ->willReturn($errors = new ListOfErrors(new Error(null, 'Something went wrong.')));

        $next = function () {
            throw new \LogicException('Not expecting next closure to be called.');
        };

        $result = ($this->middleware)($command, $next);

        $this->assertTrue($result->didFail());
        $this->assertSame($errors, $result->errors());
    }
}
