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

use Closure;

class QueueHandler implements QueueHandlerInterface
{
    /**
     * QueueHandler constructor.
     *
     * @param object $handler
     */
    public function __construct(private readonly object $handler)
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(QueueableInterface $message): void
    {
        if ($this->handler instanceof Closure) {
            ($this->handler)($message);
            return;
        }

        assert(method_exists($this->handler, 'queue'), sprintf(
            'Cannot queue "%s" - handler "%s" does not have a queue method.',
            $message::class,
            $this->handler::class,
        ));

        $this->handler->queue($message);
    }

    /**
     * @inheritDoc
     */
    public function withBatch(QueueableBatch $batch): void
    {
        if ($this->handler instanceof QueuesBatches) {
            $this->handler->withBatch($batch);
        }
    }

    /**
     * @inheritDoc
     */
    public function middleware(): array
    {
        if ($this->handler instanceof QueueThroughMiddleware) {
            return $this->handler->middleware();
        }

        return [];
    }
}