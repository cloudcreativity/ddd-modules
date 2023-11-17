<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus;

use Closure;
use RuntimeException;

final class QueryHandlerContainer implements QueryHandlerContainerInterface
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
    public function get(string $queryClass): QueryHandlerInterface
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
