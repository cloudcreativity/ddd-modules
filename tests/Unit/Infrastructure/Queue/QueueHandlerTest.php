<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Infrastructure\Queue;

use CloudCreativity\BalancedEvent\Common\Infrastructure\Queue\QueueableBatch;
use CloudCreativity\BalancedEvent\Common\Infrastructure\Queue\QueueableInterface;
use CloudCreativity\BalancedEvent\Common\Infrastructure\Queue\QueueHandler;
use PHPUnit\Framework\TestCase;

class QueueHandlerTest extends TestCase
{
    /**
     * @return void
     */
    public function testItInvokesMethodsOnInnerHandler(): void
    {
        $queueable = $this->createMock(QueueableInterface::class);
        $batch = $this->createMock(QueueableBatch::class);
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