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

use CloudCreativity\Modules\Application\InboundEventBus\Middleware\HandleInUnitOfWork;
use CloudCreativity\Modules\Contracts\Application\Messages\IntegrationEvent;
use CloudCreativity\Modules\Contracts\Application\UnitOfWork\UnitOfWorkManager;
use PHPUnit\Framework\TestCase;
use Throwable;

class HandleInUnitOfWorkTest extends TestCase
{
    /**
     * @var array<string>
     */
    private array $sequence = [];

    /**
     * @return void
     */
    public function testItCommitsUnitOfWork(): void
    {
        $event = $this->createMock(IntegrationEvent::class);

        $middleware = new HandleInUnitOfWork(
            $transactions = $this->createMock(UnitOfWorkManager::class),
            2,
        );

        $transactions
            ->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function (\Closure $callback, int $attempts) {
                $this->assertSame(2, $attempts);
                $this->sequence[] = 'begin';
                $result = $callback();
                $this->sequence[] = 'commit';
                return $result;
            });

        $middleware($event, function ($cmd) use ($event) {
            $this->assertSame($event, $cmd);
            $this->sequence[] = 'handler';
        });

        $this->assertSame(['begin', 'handler', 'commit'], $this->sequence);
    }

    /**
     * @return void
     */
    public function testItDoesNotCatchExceptions(): void
    {
        $event = $this->createMock(IntegrationEvent::class);
        $expected = new \RuntimeException('Boom! Something went wrong.');

        $middleware = new HandleInUnitOfWork(
            $transactions = $this->createMock(UnitOfWorkManager::class),
            2,
        );

        $transactions
            ->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function (\Closure $callback, int $attempts) {
                $this->assertSame(2, $attempts);
                $this->sequence[] = 'begin';
                $callback();
                $this->sequence[] = 'commit';
            });

        $actual = null;

        try {
            $middleware($event, function ($cmd) use ($event, $expected): never {
                $this->assertSame($event, $cmd);
                $this->sequence[] = 'handler';
                throw $expected;
            });
        } catch (Throwable $ex) {
            $actual = $ex;
        }

        $this->assertSame(['begin', 'handler'], $this->sequence);
        $this->assertSame($expected, $actual);
    }
}
