<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\Bus;

use CloudCreativity\Modules\Bus\Validation\Validator;
use CloudCreativity\Modules\Contracts\Messaging\Command;
use CloudCreativity\Modules\Contracts\Messaging\Message;
use CloudCreativity\Modules\Contracts\Messaging\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class ValidatorTest extends TestCase
{
    /**
     * @return array<array<class-string>>
     */
    public static function messageProvider(): array
    {
        return [
            [Query::class],
            [Command::class],
            [Message::class],
        ];
    }

    /**
     * @param class-string<Message> $class
     */
    #[DataProvider('messageProvider')]
    public function testItValidatesMessage(string $class): void
    {
        $message = $this->createStub($class);
        $error1 = new Error(null, 'Message 1');
        $error2 = new Error(null, 'Message 2');
        $error3 = new Error(null, 'Message 3');

        $a = function ($actual) use ($message, $error1): ListOfErrors {
            $this->assertSame($message, $actual);
            return new ListOfErrors($error1);
        };

        $b = function ($actual) use ($message): ?ListOfErrors {
            $this->assertSame($message, $actual);
            return null;
        };

        $c = function ($actual) use ($message, $error2, $error3): ListOfErrors {
            $this->assertSame($message, $actual);
            return new ListOfErrors($error2, $error3);
        };

        $rules = $this->createMock(PipeContainer::class);
        $rules
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'Rule2' => $b,
                'Rule3' => $c,
                default => $this->fail('Unexpected rule name: ' . $name),
            });

        $validator = new Validator(rules: $rules);
        $actual = $validator
            ->using([$a, 'Rule2', 'Rule3'])
            ->validate($message);

        $this->assertInstanceOf(ListOfErrors::class, $actual);
        $this->assertSame([$error1, $error2, $error3], $actual->all());
    }

    /**
     * @param class-string<Message> $class
     */
    #[DataProvider('messageProvider')]
    public function testItValidatesMessageUsingPsrContainer(string $class): void
    {
        $message = $this->createStub($class);
        $error1 = new Error(null, 'Message 1');
        $error2 = new Error(null, 'Message 2');
        $error3 = new Error(null, 'Message 3');

        $a = function ($actual) use ($message, $error1): Error {
            $this->assertSame($message, $actual);
            return $error1;
        };

        $b = function ($actual) use ($message): ?ListOfErrors {
            $this->assertSame($message, $actual);
            return null;
        };

        $c = function ($actual) use ($message, $error2, $error3): ListOfErrors {
            $this->assertSame($message, $actual);
            return new ListOfErrors($error2, $error3);
        };

        $rules = $this->createMock(ContainerInterface::class);
        $rules
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'Rule2' => $b,
                'Rule3' => $c,
                default => $this->fail('Unexpected rule name: ' . $name),
            });

        $validator = new Validator(rules: $rules);
        $actual = $validator
            ->using([$a, 'Rule2', 'Rule3'])
            ->validate($message);

        $this->assertInstanceOf(ListOfErrors::class, $actual);
        $this->assertSame([$error1, $error2, $error3], $actual->all());
    }

    /**
     * @param class-string<Message> $class
     */
    #[DataProvider('messageProvider')]
    public function testItStopsOnFirstFailure(string $class): void
    {
        $message = $this->createStub($class);
        $error = new Error(null, 'Message 1');

        $a = function ($actual) use ($message): null {
            $this->assertSame($message, $actual);
            return null;
        };

        $b = function ($actual) use ($message, $error): ListOfErrors {
            $this->assertSame($message, $actual);
            return new ListOfErrors($error);
        };

        $c = function (): never {
            $this->fail('Not expecting to be called.');
        };

        $validator = new Validator();
        $actual = $validator
            ->using([$a, $b, $c])
            ->stopOnFirstFailure()
            ->validate($message);

        $this->assertInstanceOf(ListOfErrors::class, $actual);
        $this->assertSame([$error], $actual->all());
    }

    public function testItHasNoRules(): void
    {
        $query = $this->createStub(Query::class);
        $validator = new Validator();

        $this->assertEquals(new ListOfErrors(), $validator->validate($query));
    }
}
