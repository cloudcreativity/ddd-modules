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

use CloudCreativity\Modules\Contracts\Application\Bus\QueryHandlerContainer;
use CloudCreativity\Modules\Contracts\Application\Messages\Query;
use CloudCreativity\Modules\Contracts\Application\Ports\Driving\Queries\QueryDispatcher as IQueryDispatcher;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;

class QueryDispatcher implements IQueryDispatcher
{
    /**
     * @var array<string|callable>
     */
    private array $pipes = [];

    /**
     * QueryDispatcher constructor.
     *
     * @param QueryHandlerContainer $handlers
     * @param PipeContainer|null $middleware
     */
    public function __construct(
        private readonly QueryHandlerContainer $handlers,
        private readonly ?PipeContainer $middleware = null,
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
    public function dispatch(Query $query): Result
    {
        $pipeline = PipelineBuilder::make($this->middleware)
            ->through($this->pipes)
            ->build(new MiddlewareProcessor(
                fn (Query $passed): Result => $this->execute($passed),
            ));

        $result = $pipeline->process($query);

        assert($result instanceof Result, 'Expecting pipeline to return a result object.');

        return $result;
    }

    /**
     * @param Query $query
     * @return Result<mixed>
     */
    private function execute(Query $query): Result
    {
        $handler = $this->handlers->get($query::class);

        $pipeline = PipelineBuilder::make($this->middleware)
            ->through($handler->middleware())
            ->build(MiddlewareProcessor::wrap($handler));

        $result = $pipeline->process($query);

        assert($result instanceof Result, 'Expecting pipeline to return a result object.');

        return $result;
    }
}
