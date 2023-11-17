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
