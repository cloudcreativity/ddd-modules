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

use CloudCreativity\Modules\Application\InboundEventBus\Middleware\TearDownAfterEvent;
use CloudCreativity\Modules\Contracts\Application\Messages\IntegrationEvent;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TearDownAfterEventTest extends TestCase
{
    /**
     * @return void
     */
    public function testItInvokesCallbackAfterSuccess(): void
    {
        $event = $this->createMock(IntegrationEvent::class);
        $sequence = [];

        $middleware = new TearDownAfterEvent(function () use (&$sequence): void {
            $sequence[] = 'teardown';
        });

        $middleware($event, function ($in) use ($event, &$sequence): void {
            $sequence[] = 'next';
            $this->assertSame($event, $in);
        });

        $this->assertSame(['next', 'teardown'], $sequence);
    }

    /**
     * @return void
     */
    public function testItInvokesCallbackAfterException(): void
    {
        $event = $this->createMock(IntegrationEvent::class);
        $exception = new RuntimeException('Something went wrong.');
        $actual = null;
        $sequence = [];

        $middleware = new TearDownAfterEvent(function () use (&$sequence): void {
            $sequence[] = 'teardown';
        });

        try {
            $middleware($event, function ($in) use ($event, $exception, &$sequence): void {
                $sequence[] = 'next';
                $this->assertSame($event, $in);
                throw $exception;
            });
            $this->fail('No exception thrown.');
        } catch (RuntimeException $ex) {
            $actual = $ex;
        }

        $this->assertSame($exception, $actual);
        $this->assertSame(['next', 'teardown'], $sequence);
    }
}
