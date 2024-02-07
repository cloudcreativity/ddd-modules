<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

namespace CloudCreativity\Modules\Infrastructure\Queue;

use CloudCreativity\Modules\Infrastructure\Infrastructure;
use CloudCreativity\Modules\Infrastructure\InfrastructureException;
use CloudCreativity\Modules\Toolkit\Iterables\ListInterface;
use CloudCreativity\Modules\Toolkit\Iterables\ListTrait;

/**
 * @implements ListInterface<QueueableInterface>
 */
final class QueueableBatch implements ListInterface
{
    /** @use ListTrait<QueueableInterface> */
    use ListTrait;

    /**
     * MessageBatch constructor.
     *
     * @param QueueableInterface ...$items
     */
    public function __construct(QueueableInterface ...$items)
    {
        Infrastructure::assert(!empty($items), 'Expecting a non-empty batch of queueable items.');

        $type = get_class($items[0]);

        for ($i = 1; $i < count($items); $i++) {
            if (!$items[$i] instanceof $type) {
                throw new InfrastructureException('Queue batch must consist of a single type of queueable item.');
            }
        }

        $this->stack = $items;
    }

    /**
     * Assert that the batch only contains queueable items of the provided class.
     *
     * @param string $expected
     * @return $this
     */
    public function ofOneType(string $expected): self
    {
        $actual = get_class($this->first());

        if ($expected !== $actual) {
            throw new InfrastructureException(sprintf(
                'Invalid queue batch - expecting "%s" when batch contains "%s" queueable items.',
                $expected,
                $actual,
            ));
        }

        return $this;
    }

    /**
     * @return QueueableInterface
     */
    public function first(): QueueableInterface
    {
        return $this->stack[0];
    }

    /**
     * @return QueueableInterface[]
     */
    public function all(): array
    {
        return $this->stack;
    }
}
