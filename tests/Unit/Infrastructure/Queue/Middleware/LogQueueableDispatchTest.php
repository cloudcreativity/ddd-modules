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
use CloudCreativity\Modules\Infrastructure\Log\ResultContext;
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\LogQueueableDispatch;
use CloudCreativity\Modules\Infrastructure\Queue\QueueableInterface;
use CloudCreativity\Modules\Toolkit\Result\Result;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogQueueableDispatchTest extends TestCase
{
    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    /**
     * @var QueueableInterface
     */
    private QueueableInterface $queueable;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->queueable = new class () implements QueueableInterface {
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
        $name = $this->queueable::class;
        $logs = [];

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $middleware = new LogQueueableDispatch($this->logger);
        $actual = $middleware($this->queueable, function (QueueableInterface $received) use ($expected) {
            $this->assertSame($this->queueable, $received);
            return $expected;
        });

        $this->assertSame($expected, $actual);
        $this->assertSame([
            [LogLevel::DEBUG, "Dispatching queued job {$name}.", ObjectContext::from($this->queueable)->context()],
            [LogLevel::INFO, "Dispatched queued job {$name}.", ResultContext::from($expected)->context()],
        ], $logs);
    }

    /**
     * @return void
     */
    public function testWithCustomLevels(): void
    {
        $expected = Result::failed('Something went wrong.');
        $name = $this->queueable::class;
        $logs = [];

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $middleware = new LogQueueableDispatch($this->logger, LogLevel::NOTICE, LogLevel::WARNING);
        $actual = $middleware($this->queueable, function (QueueableInterface $received) use ($expected) {
            $this->assertSame($this->queueable, $received);
            return $expected;
        });

        $this->assertSame($expected, $actual);
        $this->assertSame([
            [LogLevel::NOTICE, "Dispatching queued job {$name}.", ObjectContext::from($this->queueable)->context()],
            [LogLevel::WARNING, "Dispatched queued job {$name}.", ResultContext::from($expected)->context()],
        ], $logs);
    }

    /**
     * @return void
     */
    public function testItLogsAfterTheNextClosureIsInvoked(): void
    {
        $expected = new LogicException();
        $name = $this->queueable::class;

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::DEBUG, "Dispatching queued job {$name}.", ObjectContext::from($this->queueable)->context());

        $middleware = new LogQueueableDispatch($this->logger);

        try {
            $middleware($this->queueable, static function () use ($expected) {
                throw $expected;
            });
            $this->fail('No exception thrown.');
        } catch (LogicException $ex) {
            $this->assertSame($expected, $ex);
        }
    }
}
