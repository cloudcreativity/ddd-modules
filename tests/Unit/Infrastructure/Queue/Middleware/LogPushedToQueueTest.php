<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\Queue\Middleware;

use CloudCreativity\Modules\Contracts\Toolkit\Loggable\ContextFactory;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\LogPushedToQueue;
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
     * @var ContextFactory&MockObject
     */
    private ContextFactory $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->context = $this->createMock(ContextFactory::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->logger, $this->context);
    }

    public function testWithDefaultLevels(): void
    {
        $command = new class () implements Command {
            public string $foo = 'bar';
            public string $baz = 'bat';
        };

        $name = $command::class;
        $logs = [];
        $context = $this->withContext($command);

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $middleware = new LogPushedToQueue($this->logger, context: $this->context);
        $middleware(
            $command,
            function (Command $received) use ($command): void {
                $this->assertSame($command, $received);
            },
        );

        $this->assertSame([
            [LogLevel::DEBUG, "Queuing command {$name}.", ['command' => $context]],
            [LogLevel::INFO, "Queued command {$name}.", ['command' => $context]],
        ], $logs);
    }

    public function testWithCustomLevels(): void
    {
        $command = new class () implements Command {
            public string $foo = 'bar';
            public string $baz = 'bat';
        };

        $name = $command::class;
        $logs = [];
        $context = $this->withContext($command);

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $middleware = new LogPushedToQueue($this->logger, LogLevel::NOTICE, LogLevel::WARNING, $this->context);
        $middleware($command, function (Command $received) use ($command): void {
            $this->assertSame($command, $received);
        });

        $this->assertSame([
            [LogLevel::NOTICE, "Queuing command {$name}.", ['command' => $context]],
            [LogLevel::WARNING, "Queued command {$name}.", ['command' => $context]],
        ], $logs);
    }

    public function testItLogsAfterTheNextClosureIsInvoked(): void
    {
        $command = new class () implements Command {
            public string $foo = 'bar';
            public string $baz = 'bat';
        };

        $expected = new LogicException();
        $name = $command::class;
        $context = $this->withContext($command);

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::DEBUG, "Queuing command {$name}.", ['command' => $context]);

        $middleware = new LogPushedToQueue($this->logger, context: $this->context);

        try {
            $middleware($command, static function () use ($expected): never {
                throw $expected;
            });
            $this->fail('No exception thrown.');
        } catch (LogicException $ex) {
            $this->assertSame($expected, $ex);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function withContext(object $expected): array
    {
        $this->context
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($expected))
            ->willReturn($context = ['foobar' => 'bazbat!']);

        return $context;
    }
}
