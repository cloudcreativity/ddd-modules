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

use CloudCreativity\Modules\Infrastructure\Queue\QueueJobHandler;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;
use PHPUnit\Framework\TestCase;

class QueueJobHandlerTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $command = new TestQueueJob();

        $innerHandler = $this->createMock(TestQueueJobHandler::class);
        $innerHandler
            ->expects($this->once())
            ->method('execute')
            ->with($this->identicalTo($command))
            ->willReturn($expected = $this->createMock(ResultInterface::class));

        $innerHandler
            ->expects($this->once())
            ->method('middleware')
            ->willReturn($middleware = ['Middleware1', 'Middleware2']);

        $handler = new QueueJobHandler($innerHandler);

        $this->assertSame($expected, $handler($command));
        $this->assertSame($middleware, $handler->middleware());
    }

    /**
     * @return void
     */
    public function testItDoesNotHaveExecuteMethod(): void
    {
        $handler = new QueueJobHandler(new \DateTime());
        $command = new TestQueueJob();
        $commandClass = $command::class;

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage(
            "Cannot dispatch \"{$commandClass}\" - handler \"DateTime\" does not have an execute method.",
        );

        $handler($command);
    }
}
