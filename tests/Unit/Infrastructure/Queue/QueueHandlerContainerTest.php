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

use CloudCreativity\BalancedEvent\Common\Infrastructure\InfrastructureException;
use CloudCreativity\BalancedEvent\Common\Infrastructure\Queue\QueueHandler;
use CloudCreativity\BalancedEvent\Common\Infrastructure\Queue\QueueHandlerContainer;
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
        $c = fn() => true;
        $d = fn() => false;

        $container = new QueueHandlerContainer();
        $container->bind('QueueableClassA', fn() => $a);
        $container->bind('QueueableClassB', fn() => $b);
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