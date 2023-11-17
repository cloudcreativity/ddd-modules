<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Infrastructure\Queue;

use CloudCreativity\BalancedEvent\Common\Infrastructure\Infrastructure;
use CloudCreativity\BalancedEvent\Common\Infrastructure\InfrastructureException;
use CloudCreativity\BalancedEvent\Common\Infrastructure\Log\ContextProviderInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\Iterables\ListInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\Iterables\ListTrait;

class QueueableBatch implements ListInterface, ContextProviderInterface
{
    use ListTrait;

    /**
     * @var array
     */
    private array $stack;

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

    /**
     * @inheritDoc
     */
    public function context(): array
    {
        return array_map(
            fn(QueueableInterface $queueable) => $queueable->context(),
            $this->stack,
        );
    }
}
