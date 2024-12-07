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

use Closure;
use CloudCreativity\Modules\Contracts\Application\Bus\QueryHandlerContainer as IQueryHandlerContainer;
use RuntimeException;

final class QueryHandlerContainer implements IQueryHandlerContainer
{
    /**
     * @var array<string,Closure>
     */
    private array $bindings = [];

    /**
     * Bind a query handler into the container.
     *
     * @param string $queryClass
     * @param Closure $binding
     * @return void
     */
    public function bind(string $queryClass, Closure $binding): void
    {
        $this->bindings[$queryClass] = $binding;
    }

    /**
     * @inheritDoc
     */
    public function get(string $queryClass): QueryHandler
    {
        $factory = $this->bindings[$queryClass] ?? null;

        if ($factory) {
            $innerHandler = $factory();
            assert(is_object($innerHandler), "Query handler binding for {$queryClass} must return an object.");
            return new QueryHandler($innerHandler);
        }

        throw new RuntimeException('No query handler bound for query class: ' . $queryClass);
    }
}
