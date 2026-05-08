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

use CloudCreativity\Modules\Bus\Middleware\LogMessageDispatch;
use CloudCreativity\Modules\Bus\SanitizedMessage;
use CloudCreativity\Modules\Contracts\Messaging\Command;
use CloudCreativity\Modules\Contracts\Messaging\Message;
use CloudCreativity\Modules\Contracts\Messaging\Query;
use CloudCreativity\Modules\Toolkit\Result\Result;
use LogicException;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class LogMessageDispatchTest extends TestCase
{
    private LoggerInterface&Stub $logger;

    private Command $message;

    /**
     * @var array<int, mixed>
     */
    private array $logs = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createStub(LoggerInterface::class);
        $this->logger
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context): bool {
                $this->logs[] = [$level, $message, $context];
                return true;
            });

        $this->message = new class () implements Command {};
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->logger, $this->message, $this->logs);
    }

    public function testWithDefaultLevels(): void
    {
        $expected = Result::ok();
        $name = $this->message::class;

        $middleware = new LogMessageDispatch($this->logger);
        $actual = $middleware($this->message, function (Message $received) use ($expected) {
            $this->assertSame($this->message, $received);
            return $expected;
        });

        $this->assertSame($expected, $actual);
        $this->assertEquals([
            [LogLevel::DEBUG, "Bus dispatching {$name}.", ['command' => new SanitizedMessage($this->message)->context()]],
            [LogLevel::INFO, "Bus dispatched {$name}.", ['result' => $expected->context()]],
        ], $this->logs);
    }

    public function testWithCustomLevels(): void
    {
        $expected = Result::failed('Something went wrong.');
        $name = $this->message::class;

        $middleware = new LogMessageDispatch($this->logger, LogLevel::NOTICE, LogLevel::WARNING);
        $actual = $middleware($this->message, function (Message $received) use ($expected) {
            $this->assertSame($this->message, $received);
            return $expected;
        });

        $this->assertSame($expected, $actual);
        $this->assertEquals([
            [LogLevel::NOTICE, "Bus dispatching {$name}.", ['command' => new SanitizedMessage($this->message)->context()]],
            [LogLevel::WARNING, "Bus dispatched {$name}.", ['result' => $expected->context()]],
        ], $this->logs);
    }

    public function testItLogsAfterTheNextClosureIsInvoked(): void
    {
        $expected = new LogicException();
        $message = $this->createMock(Query::class);
        $name = $message::class;
        $middleware = new LogMessageDispatch($this->logger);

        try {
            $middleware($message, static function () use ($expected) {
                throw $expected;
            });
            $this->fail('No exception thrown.');
        } catch (LogicException $ex) {
            $this->assertSame($expected, $ex);
            $this->assertEquals([
                [LogLevel::DEBUG, "Bus dispatching {$name}.", ['query' => new SanitizedMessage($message)->context()]],
            ], $this->logs);
        }
    }
}
