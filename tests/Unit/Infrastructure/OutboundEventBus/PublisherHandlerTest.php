<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\OutboundEventBus;

use CloudCreativity\Modules\Contracts\Infrastructure\OutboundEventBus\PublisherHandler as IPublisherHandler;
use CloudCreativity\Modules\Infrastructure\OutboundEventBus\PublisherHandler;
use PHPUnit\Framework\TestCase;

class PublisherHandlerTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $event = new TestOutboundEvent();
        $innerHandler = $this->createMock(TestPublisher::class);

        $innerHandler
            ->expects($this->once())
            ->method('publish')
            ->with($this->identicalTo($event));

        $handler = new PublisherHandler($innerHandler);
        $handler($event);

        $this->assertInstanceOf(IPublisherHandler::class, $handler);
    }
}
