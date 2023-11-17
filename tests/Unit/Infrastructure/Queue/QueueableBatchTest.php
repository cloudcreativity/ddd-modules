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
use CloudCreativity\BalancedEvent\Common\Infrastructure\Queue\QueueableBatch;
use CloudCreativity\BalancedEvent\Common\Infrastructure\Queue\QueueableInterface;
use PHPUnit\Framework\TestCase;

class QueueableBatchTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $message1 = $this->createMock(QueueableInterface::class);
        $message2 = $this->createMock(QueueableInterface::class);
        $message3 = $this->createMock(QueueableInterface::class);

        $batch = new QueueableBatch($message1, $message2, $message3);

        $this->assertSame($batch, $batch->ofOneType(get_class($message2)));
        $this->assertSame([$message1, $message2, $message3], iterator_to_array($batch));
        $this->assertSame([$message1, $message2, $message3], $batch->all());
        $this->assertCount(3, $batch);
        $this->assertSame($message1, $batch->first());
        $this->assertFalse($batch->isEmpty());
        $this->assertTrue($batch->isNotEmpty());
    }

    /**
     * @return void
     */
    public function testEmpty(): void
    {
        $this->expectException(InfrastructureException::class);
        $this->expectExceptionMessage('non-empty');

        new QueueableBatch();
    }

    /**
     * @return void
     */
    public function testItDoesNotAllowDifferentTypesOfJobs(): void
    {
        $message1 = new TestQueueable();
        $message2 = new TestQueueable();
        $message3 = $this->createMock(QueueableInterface::class);

        $this->expectException(InfrastructureException::class);
        $this->expectExceptionMessage('Queue batch must consist of a single type of queueable item.');

        new QueueableBatch($message1, $message2, $message3);
    }

    /**
     * @return void
     */
    public function testItThrowsWhenAssertingInvalidType(): void
    {
        $message = $this->createMock(QueueableInterface::class);
        $batch = new QueueableBatch(
            new TestQueueable(),
            new TestQueueable(),
        );

        $this->expectException(InfrastructureException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid queue batch - expecting "%s" when batch contains "%s" queueable items.',
            $expected = get_class($message),
            TestQueueable::class,
        ));

        $batch->ofOneType($expected);
    }

    /**
     * @return void
     */
    public function testContext(): void
    {
        $message1 = $this->createMock(QueueableInterface::class);
        $message1->method('context')->willReturn(['foo' => 'bar']);
        $message2 = $this->createMock(QueueableInterface::class);
        $message2->method('context')->willReturn(['baz' => 'bat']);

        $batch = new QueueableBatch($message1, $message2);

        $this->assertSame([
            ['foo' => 'bar'],
            ['baz' => 'bat'],
        ], $batch->context());
    }
}
