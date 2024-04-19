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
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

class QueueJobDispatcher implements QueueJobDispatcherInterface
{
    /**
     * @var list<string|callable>
     */
    private array $pipes = [];

    /**
     * QueueJobDispatcher constructor.
     *
     * @param QueueJobHandlerContainerInterface $handlers
     * @param PipeContainerInterface|null $middleware
     */
    public function __construct(
        private readonly QueueJobHandlerContainerInterface $handlers,
        private readonly ?PipeContainerInterface $middleware = null,
    ) {
    }

    /**
     * Dispatch queue jobs through the provided pipes.
     *
     * @param list<string|callable> $pipes
     * @return void
     */
    public function through(array $pipes): void
    {
        assert(array_is_list($pipes), 'Expecting a list of pipes.');

        $this->pipes = $pipes;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(QueueJobInterface $job): ResultInterface
    {
        $handler = $this->handlers->get($job::class);

        $pipeline = PipelineBuilder::make($this->middleware)
            ->through([...$this->pipes, ...$handler->middleware()])
            ->build(MiddlewareProcessor::wrap($handler));

        $result = $pipeline->process($job);

        assert($result instanceof ResultInterface, 'Expecting pipeline to return a result object.');

        return $result;
    }
}
