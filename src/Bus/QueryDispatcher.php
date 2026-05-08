<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus;

use CloudCreativity\Modules\Contracts\Bus\QueryHandlerContainer as IQueryHandlerContainer;
use CloudCreativity\Modules\Contracts\Messaging\Query;
use CloudCreativity\Modules\Contracts\Messaging\QueryDispatcher as IQueryDispatcher;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer as IPipeContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;
use CloudCreativity\Modules\Toolkit\Pipeline\Through;
use Psr\Container\ContainerInterface;
use ReflectionClass;

abstract class QueryDispatcher implements IQueryDispatcher
{
    private readonly IQueryHandlerContainer $handlers;

    private readonly ?IPipeContainer $middleware;

    /**
     * @var array<callable|string>
     */
    private array $pipes = [];

    public function __construct(
        ContainerInterface|IQueryHandlerContainer $handlers,
        ?IPipeContainer $middleware = null,
    ) {
        $this->handlers = $handlers instanceof ContainerInterface ?
            new QueryHandlerContainer($handlers) :
            $handlers;

        $this->middleware = $middleware === null && $handlers instanceof ContainerInterface
            ? new PsrPipeContainer($handlers)
            : $middleware;

        $this->autowire();
    }

    /**
     * Dispatch messages through the provided pipes.
     *
     * @param array<callable|string> $pipes
     */
    public function through(array $pipes): void
    {
        assert(array_is_list($pipes), 'Expecting a list of pipes.');

        $this->pipes = $pipes;
    }

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


    private function autowire(): void
    {
        $reflection = new ReflectionClass($this);

        if ($this->handlers instanceof QueryHandlerContainer) {
            foreach ($reflection->getAttributes(WithQuery::class) as $attribute) {
                $instance = $attribute->newInstance();
                $this->handlers->bind($instance->query, $instance->handler);
            }
        }

        foreach ($reflection->getAttributes(Through::class) as $attribute) {
            $instance = $attribute->newInstance();
            $this->pipes = $instance->pipes;
        }
    }
}
