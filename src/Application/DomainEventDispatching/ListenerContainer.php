<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\DomainEventDispatching;

use Closure;
use RuntimeException;

final class ListenerContainer implements ListenerContainerInterface
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
