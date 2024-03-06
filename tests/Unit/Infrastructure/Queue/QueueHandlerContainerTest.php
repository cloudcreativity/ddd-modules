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

use CloudCreativity\Modules\Infrastructure\InfrastructureException;
use CloudCreativity\Modules\Infrastructure\Queue\QueueHandler;
use CloudCreativity\Modules\Infrastructure\Queue\QueueHandlerContainer;
use PHPUnit\Framework\TestCase;

class QueueHandlerContainerTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $a = new TestQueueHandler();
        $b = $this->createMock(TestQueueHandler::class);
        $c = fn () => true;
        $d = fn () => false;

        $container = new QueueHandlerContainer();
        $container->bind('QueueableClassA', fn () => $a);
        $container->bind('QueueableClassB', fn () => $b);
        $container->register('QueueableClassC', $c);
        $container->register('QueueableClassD', $d);

        $this->assertEquals(new QueueHandler($a), $container->get('QueueableClassA'));
        $this->assertEquals(new QueueHandler($b), $container->get('QueueableClassB'));
        $this->assertEquals(new QueueHandler($c), $container->get('QueueableClassC'));
        $this->assertEquals(new QueueHandler($d), $container->get('QueueableClassD'));

        $this->expectException(InfrastructureException::class);
        $this->expectExceptionMessage('No queue handler bound for queueable class: QueueableClassE');

        $container->get('QueueableClassE');
    }
}
