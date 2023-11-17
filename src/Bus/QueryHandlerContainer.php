<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Bus;

use Closure;
use RuntimeException;

class QueryHandlerContainer implements QueryHandlerContainerInterface
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