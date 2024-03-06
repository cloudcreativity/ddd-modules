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
use CloudCreativity\Modules\Infrastructure\Queue\QueueableBatch;
use CloudCreativity\Modules\Infrastructure\Queue\QueueableInterface;
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
}
