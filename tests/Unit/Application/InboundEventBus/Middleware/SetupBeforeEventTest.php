<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\InboundEventBus\Middleware;

use Closure;
use CloudCreativity\Modules\Application\InboundEventBus\Middleware\SetupBeforeEvent;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\IntegrationEvent;
use CloudCreativity\Modules\Toolkit\Result\Result;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SetupBeforeEventTest extends TestCase
{
    /**
     * @var array<string>
     */
    private array $sequence = [];

    /**
     * @return void
     */
    public function testItSetsUpAndSucceedsWithoutTeardown(): void
    {
        $event = $this->createMock(IntegrationEvent::class);

        $middleware = new SetupBeforeEvent(function () {
            $this->sequence[] = 'setup';
            return null;
        });

        $middleware($event, function ($in) use ($event): void {
            $this->sequence[] = 'next';
            $this->assertSame($event, $in);
        });

        $this->assertSame(['setup', 'next'], $this->sequence);
    }

    /**
     * @return void
     */
    public function testItSetsUpAndSucceedsWithTeardown(): void
    {
        $event = $this->createMock(IntegrationEvent::class);

        $middleware = new SetupBeforeEvent(function (): Closure {
            $this->sequence[] = 'setup';
            return function (): void {
                $this->sequence[] = 'teardown';
            };
        });

        $middleware($event, function ($in) use ($event): void {
            $this->sequence[] = 'next';
            $this->assertSame($event, $in);
        });

        $this->assertSame(['setup', 'next', 'teardown'], $this->sequence);
    }

    /**
     * @return void
     */
    public function testItSetsUpAndThrowsExceptionWithoutTeardown(): void
    {
        $event = $this->createMock(IntegrationEvent::class);
        $exception = new RuntimeException('Something went wrong.');
        $actual = null;

        $middleware = new SetupBeforeEvent(function () {
            $this->sequence[] = 'setup';
            return null;
        });

        try {
            $middleware($event, function ($in) use ($event, $exception): Result {
                $this->sequence[] = 'next';
                $this->assertSame($event, $in);
                throw $exception;
            });
            $this->fail('No exception thrown.');
        } catch (RuntimeException $ex) {
            $actual = $ex;
        }

        $this->assertSame($exception, $actual);
        $this->assertSame(['setup', 'next'], $this->sequence);
    }

    /**
     * @return void
     */
    public function testItSetsUpAndThrowsExceptionWithTeardown(): void
    {
        $event = $this->createMock(IntegrationEvent::class);
        $exception = new RuntimeException('Something went wrong.');
        $actual = null;

        $middleware = new SetupBeforeEvent(function (): Closure {
            $this->sequence[] = 'setup';
            return function (): void {
                $this->sequence[] = 'teardown';
            };
        });

        try {
            $middleware($event, function ($in) use ($event, $exception): Result {
                $this->sequence[] = 'next';
                $this->assertSame($event, $in);
                throw $exception;
            });
            $this->fail('No exception thrown.');
        } catch (RuntimeException $ex) {
            $actual = $ex;
        }

        $this->assertSame($exception, $actual);
        $this->assertSame(['setup', 'next', 'teardown'], $this->sequence);
    }
}
