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

use Closure;
use RuntimeException;

final class QueueJobHandlerContainer implements QueueJobHandlerContainerInterface
{
    /**
     * @var array<string, Closure>
     */
    private array $bindings = [];

    /**
     * Bind a queue job handler into the container.
     *
     * @param string $jobClass
     * @param Closure $binding
     * @return void
     */
    public function bind(string $jobClass, Closure $binding): void
    {
        $this->bindings[$jobClass] = $binding;
    }

    /**
     * @inheritDoc
     */
    public function get(string $jobClass): QueueJobHandlerInterface
    {
        $factory = $this->bindings[$jobClass] ?? null;

        if ($factory) {
            $innerHandler = $factory();
            assert(is_object($innerHandler), "Queue job handler binding for {$jobClass} must return an object.");
            return new QueueJobHandler($innerHandler);
        }

        throw new RuntimeException('No queue job handler bound for job class: ' . $jobClass);
    }
}
