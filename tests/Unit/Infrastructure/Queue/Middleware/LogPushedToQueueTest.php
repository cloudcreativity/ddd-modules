<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\Queue\Middleware;

use CloudCreativity\Modules\Infrastructure\Log\ObjectContext;
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\LogPushedToQueue;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogPushedToQueueTest extends TestCase
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
        $name = $this->message::class;
        $logs = [];

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $middleware = new LogPushedToQueue($this->logger);
        $middleware($this->message, function (CommandInterface $received): void {
            $this->assertSame($this->message, $received);
        });

        $context = ObjectContext::from($this->message)->context();

        $this->assertSame([
            [LogLevel::DEBUG, "Queuing command {$name}.", $context],
            [LogLevel::INFO, "Queued command {$name}.", $context],
        ], $logs);
    }

    /**
     * @return void
     */
    public function testWithCustomLevels(): void
    {
        $name = $this->message::class;
        $logs = [];

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $middleware = new LogPushedToQueue($this->logger, LogLevel::NOTICE, LogLevel::WARNING);
        $middleware($this->message, function (CommandInterface $received): void {
            $this->assertSame($this->message, $received);
        });

        $context = ObjectContext::from($this->message)->context();

        $this->assertSame([
            [LogLevel::NOTICE, "Queuing command {$name}.", $context],
            [LogLevel::WARNING, "Queued command {$name}.", $context],
        ], $logs);
    }

    /**
     * @return void
     */
    public function testItLogsAfterTheNextClosureIsInvoked(): void
    {
        $expected = new LogicException();
        $name = $this->message::class;

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::DEBUG, "Queuing command {$name}.", ObjectContext::from($this->message)->context());

        $middleware = new LogPushedToQueue($this->logger);

        try {
            $middleware($this->message, static function () use ($expected): never {
                throw $expected;
            });
            $this->fail('No exception thrown.');
        } catch (LogicException $ex) {
            $this->assertSame($expected, $ex);
        }
    }
}
