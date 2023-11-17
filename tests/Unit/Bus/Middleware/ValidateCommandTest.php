<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
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
