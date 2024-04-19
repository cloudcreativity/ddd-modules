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

use CloudCreativity\Modules\Infrastructure\Queue\Middleware\LogJobDispatch;
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;
use CloudCreativity\Modules\Toolkit\Loggable\ObjectContext;
use CloudCreativity\Modules\Toolkit\Loggable\ResultContext;
use CloudCreativity\Modules\Toolkit\Result\Result;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogJobDispatchTest extends TestCase
{
    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    /**
     * @var QueueJobInterface
     */
    private QueueJobInterface $job;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->job = new class () implements QueueJobInterface {
            public string $foo = 'bar';
            public string $baz = 'bat';
        };
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->logger, $this->job);
    }

    /**
     * @return void
     */
    public function testWithDefaultLevels(): void
    {
        $expected = Result::ok()->withMeta(['foobar' => 'bazbat']);
        $name = $this->job::class;
        $logs = [];

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $middleware = new LogJobDispatch($this->logger);
        $actual = $middleware($this->job, function (QueueJobInterface $received) use ($expected) {
            $this->assertSame($this->job, $received);
            return $expected;
        });

        $this->assertSame($expected, $actual);
        $this->assertSame([
            [LogLevel::DEBUG, "Queue bus dispatching {$name}.", ObjectContext::from($this->job)->context()],
            [LogLevel::INFO, "Queue bus dispatched {$name}.", ResultContext::from($expected)->context()],
        ], $logs);
    }

    /**
     * @return void
     */
    public function testWithCustomLevels(): void
    {
        $expected = Result::failed('Something went wrong.');
        $name = $this->job::class;
        $logs = [];

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$logs): bool {
                $logs[] = [$level, $message, $context];
                return true;
            });

        $middleware = new LogJobDispatch($this->logger, LogLevel::NOTICE, LogLevel::WARNING);
        $actual = $middleware($this->job, function (QueueJobInterface $received) use ($expected) {
            $this->assertSame($this->job, $received);
            return $expected;
        });

        $this->assertSame($expected, $actual);
        $this->assertSame([
            [LogLevel::NOTICE, "Queue bus dispatching {$name}.", ObjectContext::from($this->job)->context()],
            [LogLevel::WARNING, "Queue bus dispatched {$name}.", ResultContext::from($expected)->context()],
        ], $logs);
    }

    /**
     * @return void
     */
    public function testItLogsAfterTheNextClosureIsInvoked(): void
    {
        $expected = new LogicException();
        $job = $this->createMock(QueueJobInterface::class);
        $name = $job::class;

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::DEBUG, "Queue bus dispatching {$name}.", ObjectContext::from($job)->context());

        $middleware = new LogJobDispatch($this->logger);

        try {
            $middleware($job, static function () use ($expected) {
                throw $expected;
            });
            $this->fail('No exception thrown.');
        } catch (LogicException $ex) {
            $this->assertSame($expected, $ex);
        }
    }
}
