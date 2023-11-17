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
