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

use Closure;
use CloudCreativity\Modules\Contracts\Bus\QueryHandlerContainer as IQueryHandlerContainer;
use CloudCreativity\Modules\Contracts\Messaging\Query;
use Psr\Container\ContainerInterface;

final class QueryHandlerContainer implements IQueryHandlerContainer
{
    /**
     * @var array<class-string<Query>, class-string|Closure>
     */
    private array $bindings = [];

    public function __construct(private readonly ?ContainerInterface $container = null)
    {
    }

    /**
     * Bind a query handler into the container.
     *
     * @param class-string<Query> $queryClass
     * @param class-string|(Closure(): object) $binding
     */
    public function bind(string $queryClass, Closure|string $binding): void
    {
        if (is_string($binding) && !$this->container) {
            throw new BusException('Cannot use a string query handler binding without a PSR container.');
        }

        $this->bindings[$queryClass] = $binding;
    }

    public function get(string $queryClass): QueryHandler
    {
        $binding = $this->bindings[$queryClass] ?? null;

        if ($binding instanceof Closure) {
            $instance = $binding();
            assert(is_object($instance), "Query handler binding for {$queryClass} must return an object.");
            return new QueryHandler($instance);
        }

        if (is_string($binding)) {
            $instance = $this->container?->get($binding);
            assert(is_object($instance), "PSR container query handler binding {$binding} is not an object.");
            return new QueryHandler($instance);
        }

        throw new BusException('No query handler bound for query class: ' . $queryClass);
    }
}
