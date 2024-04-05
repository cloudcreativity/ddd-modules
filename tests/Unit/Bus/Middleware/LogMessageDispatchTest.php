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

use CloudCreativity\Modules\Bus\Middleware\LogBusDispatch;
use CloudCreativity\Modules\Toolkit\Loggable\ObjectContext;
use CloudCreativity\Modules\Toolkit\Loggable\ResultContext;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Messages\MessageInterface;
use CloudCreativity\Modules\Toolkit\Messages\QueryInterface;
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
    private LoggerInterface $logger;

    /**
     * @var CommandInterface
     */
    private CommandInterface $message;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->message = new class () implements CommandInterface {
            public string $foo = 'bar';
            public string $baz = 'bat';
        };
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

        $middleware = new LogBusDispatch($this->logger);
        $actual = $middleware($this->message, function (MessageInterface $received) use ($expected) {
            $this->assertSame($this->message, $received);
            return $expected;
        });

        $this->assertSame($expected, $actual);
        $this->assertSame([
            [LogLevel::DEBUG, "Bus dispatching {$name}.", ObjectContext::from($this->message)->context()],
            [LogLevel::INFO, "Bus dispatched {$name}.", ResultContext::from($expected)->context()],
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

        $middleware = new LogBusDispatch($this->logger, LogLevel::NOTICE, LogLevel::WARNING);
        $actual = $middleware($this->message, function (MessageInterface $received) use ($expected) {
            $this->assertSame($this->message, $received);
            return $expected;
        });

        $this->assertSame($expected, $actual);
        $this->assertSame([
            [LogLevel::NOTICE, "Bus dispatching {$name}.", ObjectContext::from($this->message)->context()],
            [LogLevel::WARNING, "Bus dispatched {$name}.", ResultContext::from($expected)->context()],
        ], $logs);
    }

    /**
     * @return void
     */
    public function testItLogsAfterTheNextClosureIsInvoked(): void
    {
        $expected = new LogicException();
        $message = $this->createMock(QueryInterface::class);
        $name = $message::class;

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::DEBUG, "Bus dispatching {$name}.", ObjectContext::from($message)->context());

        $middleware = new LogBusDispatch($this->logger);

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
