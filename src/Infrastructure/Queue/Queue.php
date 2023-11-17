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

use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipelineBuilderFactory;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipelineBuilderFactoryInterface;

class Queue implements QueueInterface
{
    /**
     * @var array
     */
    private array $pipes = [];

    /**
     * Queue constructor.
     *
     * @param QueueHandlerContainerInterface $handlers
     * @param PipelineBuilderFactoryInterface $pipelineFactory
     */
    public function __construct(
        private readonly QueueHandlerContainerInterface $handlers,
        private readonly PipelineBuilderFactoryInterface $pipelineFactory = new PipelineBuilderFactory()
    ) {
    }

    /**
     * Push jobs through the provided pipes when queuing them.
     *
     * @param array $pipes
     * @return void
     */
    public function through(array $pipes): void
    {
        $this->pipes = array_values($pipes);
    }

    /**
     * @inheritDoc
     */
    public function push(QueueableInterface $queueable): void
    {
        $this->pushBatch(new QueueableBatch($queueable));
    }

    /**
     * @inheritDoc
     */
    public function pushBatch(QueueableBatch $batch): void
    {
        $handler = $this->handlers->get(
            $batch->first()::class
        );

        $pipeline = $this->pipelineFactory
            ->getPipelineBuilder()
            ->through([...$this->pipes, ...array_values($handler->middleware())])
            ->build(new MiddlewareProcessor(
                static function (QueueableInterface $message) use ($handler): QueueableInterface {
                    $handler($message);
                    return $message;
                },
            ));

        $handler->withBatch($batch);

        foreach ($batch as $queueable) {
            $pipeline->process($queueable);
        }
    }
}
