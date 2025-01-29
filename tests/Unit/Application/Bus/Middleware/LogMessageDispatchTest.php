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

use CloudCreativity\Modules\Application\Bus\Middleware\LogMessageDispatch;
use CloudCreativity\Modules\Contracts\Toolkit\Loggable\ContextFactory;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Message;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Query;
use CloudCreativity\Modules\Toolkit\Result\Result;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogMessageDispatchTest extends TestCase
{
    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface&MockObject $logger;

    /**
     * @var MockObject&ContextFactory
     */
    private ContextFactory&MockObject $context;

    /**
     * @var Command
     */
    private Command $message;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->context = $this->createMock(ContextFactory::class);
        $this->message = new class () implements Command {};
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->logger, $this->context, $this->message);
    }

    /**
     * @return void
     */
    public function testWithDefaultLevels(): void
    {
        $expected = Result::ok()->withMeta(['foobar' => 'bazbat']);
        $name = $this->message::class;
        $logs = [];

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $context1 = ['message' => 'blah!'];
        $context2 = ['result' => 'blah!'];

        $this->context
            ->expects($this->exactly(2))
            ->method('make')
            ->willReturnCallback(fn (object $in) => match ($in) {
                $this->message => $context1,
                $expected => $context2,
                default => $this->fail('Unexpected object to convert to context.'),
            });

        $middleware = new LogMessageDispatch($this->logger, context: $this->context);
        $actual = $middleware($this->message, function (Message $received) use ($expected) {
            $this->assertSame($this->message, $received);
            return $expected;
        });

        $this->assertSame($expected, $actual);
        $this->assertSame([
            [LogLevel::DEBUG, "Bus dispatching {$name}.", ['command' => $context1]],
            [LogLevel::INFO, "Bus dispatched {$name}.", ['result' => $context2]],
        ], $logs);
    }

    /**
     * @return void
     */
    public function testWithCustomLevels(): void
    {
        $expected = Result::failed('Something went wrong.');
        $name = $this->message::class;
        $logs = [];

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $context1 = ['message' => 'blah!'];
        $context2 = ['result' => 'blah!'];

        $this->context
            ->expects($this->exactly(2))
            ->method('make')
            ->willReturnCallback(fn (object $in) => match ($in) {
                $this->message => $context1,
                $expected => $context2,
                default => $this->fail('Unexpected object to convert to context.'),
            });


        $middleware = new LogMessageDispatch($this->logger, LogLevel::NOTICE, LogLevel::WARNING, $this->context);
        $actual = $middleware($this->message, function (Message $received) use ($expected) {
            $this->assertSame($this->message, $received);
            return $expected;
        });

        $this->assertSame($expected, $actual);
        $this->assertSame([
            [LogLevel::NOTICE, "Bus dispatching {$name}.", ['command' => $context1]],
            [LogLevel::WARNING, "Bus dispatched {$name}.", ['result' => $context2]],
        ], $logs);
    }

    /**
     * @return void
     */
    public function testItLogsAfterTheNextClosureIsInvoked(): void
    {
        $expected = new LogicException();
        $message = $this->createMock(Query::class);
        $name = $message::class;

        $this->context
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($message))
            ->willReturn($context = ['foo' => 'bar']);

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::DEBUG, "Bus dispatching {$name}.", ['query' => $context]);

        $middleware = new LogMessageDispatch($this->logger, context: $this->context);

        try {
            $middleware($message, static function () use ($expected) {
                throw $expected;
            });
            $this->fail('No exception thrown.');
        } catch (LogicException $ex) {
            $this->assertSame($expected, $ex);
        }
    }
}
