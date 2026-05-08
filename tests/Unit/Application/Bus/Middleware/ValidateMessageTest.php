<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\Bus\Middleware;

use CloudCreativity\Modules\Bus\Middleware\ValidateMessage;
use CloudCreativity\Modules\Contracts\Bus\Validation\Bail;
use CloudCreativity\Modules\Contracts\Bus\Validation\Validator;
use CloudCreativity\Modules\Contracts\Messaging\Command;
use CloudCreativity\Modules\Contracts\Messaging\Message;
use CloudCreativity\Modules\Contracts\Messaging\Query;
use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;
use CloudCreativity\Modules\Toolkit\Result\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ValidateMessageTest extends TestCase
{
    /**
     * @var MockObject&Validator
     */
    private Validator $validator;

    private ValidateMessage $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = $this->createMock(Validator::class);

        $this->middleware = new class ($this->validator) extends ValidateMessage {
            /**
             * @return iterable<string>
             */
            protected function rules(): iterable
            {
                return ['foo', 'bar'];
            }
        };
    }

    public function testItSucceeds(): void
    {
        $rules = [];
        $command = $this->createStub(Command::class);
        $expected = Result::ok();

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
            ->method('stopOnFirstFailure')
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

    public function testItFails(): void
    {
        $this->validator
            ->expects($this->once())
            ->method('using')
            ->willReturnSelf();

        $this->validator
            ->expects($this->once())
            ->method('stopOnFirstFailure')
            ->with(false)
            ->willReturnSelf();

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($command = $this->createStub(Query::class))
            ->willReturn($errors = new ListOfErrors(new Error(null, 'Something went wrong.')));

        $next = function () {
            throw new \LogicException('Not expecting next closure to be called.');
        };

        $result = ($this->middleware)($command, $next);

        $this->assertTrue($result?->didFail());
        $this->assertSame($errors, $result->errors());
    }

    public function testItStopsOnFirstFailureViaBail(): void
    {
        $this->middleware = new class ($this->validator) extends ValidateMessage implements Bail {
            /**
             * @return iterable<string>
             */
            protected function rules(): iterable
            {
                return ['foo', 'bar'];
            }
        };

        $this->validator
            ->expects($this->once())
            ->method('using')
            ->willReturnSelf();

        $this->validator
            ->expects($this->once())
            ->method('stopOnFirstFailure')
            ->with(true)
            ->willReturnSelf();

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($command = $this->createStub(Message::class))
            ->willReturn($errors = new ListOfErrors(new Error(null, 'Something went wrong.')));

        $next = function () {
            throw new \LogicException('Not expecting next closure to be called.');
        };

        $result = ($this->middleware)($command, $next);

        $this->assertTrue($result?->didFail());
        $this->assertSame($errors, $result->errors());
    }

    public function testItStopsOnFirstFailure(): void
    {
        $command = $this->createStub(Command::class);

        $this->middleware = new class ($command, $this->validator) extends ValidateMessage {
            public function __construct(private Message $message, Validator $validator)
            {
                parent::__construct($validator);
            }

            /**
             * @return iterable<string>
             */
            protected function rules(): iterable
            {
                return ['foo', 'bar'];
            }

            protected function stopOnFirstFailure(Message $message): bool
            {
                return $this->message === $message;
            }
        };

        $this->validator
            ->expects($this->once())
            ->method('using')
            ->willReturnSelf();

        $this->validator
            ->expects($this->once())
            ->method('stopOnFirstFailure')
            ->with(true)
            ->willReturnSelf();

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($command)
            ->willReturn($errors = new ListOfErrors(new Error(null, 'Something went wrong.')));

        $next = function () {
            throw new \LogicException('Not expecting next closure to be called.');
        };

        $result = ($this->middleware)($command, $next);

        $this->assertTrue($result?->didFail());
        $this->assertSame($errors, $result->errors());
    }
}
