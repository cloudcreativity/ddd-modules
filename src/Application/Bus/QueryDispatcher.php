<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus;

use CloudCreativity\Modules\Application\Ports\Driving\Queries\QueryDispatcherInterface;
use CloudCreativity\Modules\Toolkit\Messages\QueryInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

class QueryDispatcher implements QueryDispatcherInterface
{
    /**
     * @var array<string|callable>
     */
    private array $pipes = [];

    /**
     * QueryDispatcher constructor.
     *
     * @param QueryHandlerContainerInterface $handlers
     * @param PipeContainerInterface|null $middleware
     */
    public function __construct(
        private readonly QueryHandlerContainerInterface $handlers,
        private readonly ?PipeContainerInterface $middleware = null,
    ) {
    }

    /**
     * Dispatch messages through the provided pipes.
     *
     * @param array<string|callable> $pipes
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
    public function dispatch(QueryInterface $query): ResultInterface
    {
        $handler = $this->handlers->get($query::class);

        $pipeline = PipelineBuilder::make($this->middleware)
            ->through([...$this->pipes, ...array_values($handler->middleware())])
            ->build(MiddlewareProcessor::wrap($handler));

        $result = $pipeline->process($query);

        assert($result instanceof ResultInterface, 'Expecting pipeline to return a result object.');

        return $result;
    }
}
