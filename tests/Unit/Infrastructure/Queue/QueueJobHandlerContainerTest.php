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
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobHandlerContainer;
use PHPUnit\Framework\TestCase;

class QueueJobHandlerContainerTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $a = new TestQueueJobHandler();
        $b = $this->createMock(TestQueueJobHandlerInterface::class);

        $container = new QueueJobHandlerContainer();
        $container->bind('JobClassA', fn () => $a);
        $container->bind('JobClassB', fn () => $b);

        $this->assertEquals(new QueueJobHandler($a), $container->get('JobClassA'));
        $this->assertEquals(new QueueJobHandler($b), $container->get('JobClassB'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No queue job handler bound for job class: JobClassC');

        $container->get('JobClassC');
    }
}
