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

use Closure;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactory;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactoryInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

class Queue implements QueueInterface
{
    /**
     * @var EnqueuerInterface
     */
    private readonly EnqueuerInterface $enqueuer;

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
     * @param EnqueuerInterface|Closure(QueueableInterface): void $enqueuer
     * @param QueueHandlerContainerInterface $handlers
     * @param PipelineBuilderFactoryInterface|PipeContainerInterface|null $pipeline
     */
    public function __construct(
        EnqueuerInterface|Closure $enqueuer,
        private readonly QueueHandlerContainerInterface $handlers,
        PipelineBuilderFactoryInterface|PipeContainerInterface|null $pipeline = null,
    ) {
        $this->enqueuer = ($enqueuer instanceof Closure) ? new ClosureEnqueuer($enqueuer) : $enqueuer;
        $this->pipelineFactory = PipelineBuilderFactory::make($pipeline);
    }

    /**
     * Dispatch messages through the provided pipes.
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
    public function push(QueueableInterface|iterable $queueable): void
    {
        if ($queueable instanceof QueueableInterface) {
            $this->enqueuer->queue($queueable);
            return;
        }

        foreach ($queueable as $item) {
            $this->enqueuer->queue($item);
        }
    }

    /**
     * @inheritDoc
     */
    public function dispatch(QueueableInterface $queueable): ResultInterface
    {
        $handler = $this->handlers->get($queueable::class);

        $pipeline = $this->pipelineFactory
            ->getPipelineBuilder()
            ->through([...$this->pipes, ...array_values($handler->middleware())])
            ->build(MiddlewareProcessor::wrap($handler));

        $result = $pipeline->process($queueable);

        assert($result instanceof ResultInterface, 'Expecting pipeline to return a result object.');

        return $result;
    }
}
