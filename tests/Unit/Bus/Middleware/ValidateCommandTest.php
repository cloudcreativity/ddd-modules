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

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Bus\Middleware;

use CloudCreativity\BalancedEvent\Common\Bus\CommandInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Middleware\ValidateCommand;
use CloudCreativity\BalancedEvent\Common\Bus\Results\Error;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ListOfErrors;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ResultInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Validation\CommandValidatorInterface;
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

        $this->middleware = new class($this->validator) extends ValidateCommand {
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
