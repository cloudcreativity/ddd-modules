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

use CloudCreativity\Modules\Infrastructure\Queue\Middleware\LogPushedToQueue;
use CloudCreativity\Modules\Toolkit\Loggable\ObjectContext;
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
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->logger);
    }

    /**
     * @return void
     */
    public function testWithDefaultLevels(): void
    {
        $command = new class () implements CommandInterface {
            public string $foo = 'bar';
            public string $baz = 'bat';
        };

        $name = $command::class;
        $logs = [];

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $middleware = new LogPushedToQueue($this->logger);
        $middleware(
            $command,
            function (CommandInterface $received) use ($command): void {
                $this->assertSame($command, $received);
            },
        );

        $context = ObjectContext::from($command)->context();

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
        $command = new class () implements CommandInterface {
            public string $foo = 'bar';
            public string $baz = 'bat';
        };

        $name = $command::class;
        $logs = [];

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $middleware = new LogPushedToQueue($this->logger, LogLevel::NOTICE, LogLevel::WARNING);
        $middleware($command, function (CommandInterface $received) use ($command): void {
            $this->assertSame($command, $received);
        });

        $context = ObjectContext::from($command)->context();

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
        $command = new class () implements CommandInterface {
            public string $foo = 'bar';
            public string $baz = 'bat';
        };

        $expected = new LogicException();
        $name = $command::class;

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::DEBUG, "Queuing command {$name}.", ObjectContext::from($command)->context());

        $middleware = new LogPushedToQueue($this->logger);

        try {
            $middleware($command, static function () use ($expected): never {
                throw $expected;
            });
            $this->fail('No exception thrown.');
        } catch (LogicException $ex) {
            $this->assertSame($expected, $ex);
        }
    }
}
