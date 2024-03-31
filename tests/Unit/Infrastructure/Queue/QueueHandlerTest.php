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

use CloudCreativity\Modules\Infrastructure\Queue\QueueableInterface;
use CloudCreativity\Modules\Infrastructure\Queue\QueueHandler;
use CloudCreativity\Modules\Toolkit\Result\Result;
use PHPUnit\Framework\TestCase;

class QueueHandlerTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $queueable = $this->createMock(QueueableInterface::class);
        $innerHandler = $this->createMock(TestQueueHandler::class);

        $innerHandler
            ->expects($this->once())
            ->method('execute')
            ->with($this->identicalTo($queueable))
            ->willReturn($expected = Result::ok());

        $innerHandler
            ->method('middleware')
            ->willReturn($middleware = ['Middleware1', 'Middleware2']);

        $handler = new QueueHandler($innerHandler);
        $actual = $handler($queueable);

        $this->assertSame($middleware, $handler->middleware());
        $this->assertSame($expected, $actual);
    }
}
