<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Infrastructure\DomainEventDispatching;

use Closure;
use RuntimeException;

class ListenerContainer implements ListenerContainerInterface
{
    /**
     * @var array<string,Closure>
     */
    private array $bindings = [];

    /**
     * Bind a listener factory into the container.
     *
     * @param string $listenerName
     * @param Closure $binding
     * @return void
     */
    public function bind(string $listenerName, Closure $binding): void
    {
        $this->bindings[$listenerName] = $binding;
    }

    /**
     * @inheritDoc
     */
    public function get(string $listenerName): object
    {
        $factory = $this->bindings[$listenerName] ?? null;

        if ($factory) {
            $listener = $factory();
            assert(is_object($listener), "Listener binding for {$listenerName} must return an object.");
            return $listener;
        }

        throw new RuntimeException('Unrecognised listener name: ' . $listenerName);
    }
}
