<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\Queue;

use CloudCreativity\Modules\Infrastructure\Queue\QueueableBatch;
use CloudCreativity\Modules\Infrastructure\Queue\QueueableInterface;
use CloudCreativity\Modules\Infrastructure\Queue\QueueHandler;
use PHPUnit\Framework\TestCase;

class QueueHandlerTest extends TestCase
{
    /**
     * @return void
     */
    public function testItInvokesMethodsOnInnerHandler(): void
    {
        $queueable = $this->createMock(QueueableInterface::class);
        $batch = new QueueableBatch(new TestQueueable());
        $innerHandler = $this->createMock(TestQueueHandler::class);

        $innerHandler
            ->expects($this->once())
            ->method('queue')
            ->with($this->identicalTo($queueable));

        $innerHandler
            ->expects($this->once())
            ->method('withBatch')
            ->with($this->identicalTo($batch));

        $innerHandler
            ->method('middleware')
            ->willReturn($middleware = ['Middleware1', 'Middleware2']);

        $handler = new QueueHandler($innerHandler);
        $handler->withBatch($batch);
        $handler($queueable);
        $this->assertSame($middleware, $handler->middleware());
    }

    /**
     * @return void
     */
    public function testItInvokesClosure(): void
    {
        $called = false;
        $queueable = $this->createMock(QueueableInterface::class);
        $fn = function (QueueableInterface $passed) use ($queueable, &$called): void {
            $this->assertSame($queueable, $passed);
            $called = true;
        };

        $handler = new QueueHandler($fn);
        $handler->withBatch(new QueueableBatch($queueable));
        $handler($queueable);

        $this->assertTrue($called);
        $this->assertSame([], $handler->middleware());
    }
}
