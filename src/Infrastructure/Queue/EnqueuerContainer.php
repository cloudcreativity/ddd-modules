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
use CloudCreativity\Modules\Contracts\Infrastructure\Queue\EnqueuerContainer as IEnqueuerContainer;

final class EnqueuerContainer implements IEnqueuerContainer
{
    /**
     * @var array<string, Closure>
     */
    private array $bindings = [];

    /**
     * @param Closure(): object $default
     */
    public function __construct(private readonly Closure $default)
    {
    }

    /**
     * Bind an enqueuer factory into the container.
     *
     * @param string $queueableName
     * @param Closure $binding
     * @return void
     */
    public function bind(string $queueableName, Closure $binding): void
    {
        $this->bindings[$queueableName] = $binding;
    }

    /**
     * @inheritDoc
     */
    public function get(string $command): Enqueuer
    {
        $factory = $this->bindings[$command] ?? $this->default;

        $enqueuer = $factory();

        assert(is_object($enqueuer), "Enqueuer binding for {$command} must return an object.");

        return new Enqueuer($enqueuer);
    }
}
