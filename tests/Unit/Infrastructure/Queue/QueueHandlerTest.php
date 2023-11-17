<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
