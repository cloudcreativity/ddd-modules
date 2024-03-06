<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
