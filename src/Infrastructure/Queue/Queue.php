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

use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactory;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactoryInterface;

class Queue implements QueueInterface
{
    /**
     * @var PipelineBuilderFactoryInterface
     */
    private readonly PipelineBuilderFactoryInterface $pipelineFactory;

    /**
     * @var array<string|callable>
     */
    private array $pipes = [];

    /**
     * Queue constructor.
     *
     * @param QueueHandlerContainerInterface $handlers
     * @param PipelineBuilderFactoryInterface|PipeContainerInterface|null $pipeline
     */
    public function __construct(
        private readonly QueueHandlerContainerInterface $handlers,
        PipelineBuilderFactoryInterface|PipeContainerInterface|null $pipeline = null,
    ) {
        $this->pipelineFactory = PipelineBuilderFactory::make($pipeline);
    }

    /**
     * Push jobs through the provided pipes when queuing them.
     *
     * @param array<string|callable> $pipes
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
            $batch->first()::class,
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
