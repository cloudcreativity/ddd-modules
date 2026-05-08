<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Queue;

use Closure;
use CloudCreativity\Modules\Contracts\Infrastructure\Queue\EnqueuerContainer as IEnqueuerContainer;
use CloudCreativity\Modules\Contracts\Messaging\Command;
use CloudCreativity\Modules\Infrastructure\InfrastructureException;
use Psr\Container\ContainerInterface;

final class EnqueuerContainer implements IEnqueuerContainer
{
    /**
     * @var array<class-string<Command>, Closure|string>
     */
    private array $bindings = [];

    /**
     * @param (Closure(): object)|string|null $default
     */
    public function __construct(
        private Closure|string|null $default = null,
        private readonly ?ContainerInterface $container = null,
    ) {
        if (is_string($this->default) && $this->container === null) {
            throw new InfrastructureException(
                'Cannot bind default enqueuer as a string without a PSR container.',
            );
        }
    }

    /**
     * Bind an enqueuer factory into the container.
     *
     * @param array<class-string<Command>>|class-string<Command> $queueableName
     * @param (Closure(): object)|string $binding
     */
    public function bind(array|string $queueableName, Closure|string $binding): void
    {
        if (is_string($queueableName)) {
            $queueableName = [$queueableName];
        }

        foreach ($queueableName as $queueable) {
            $this->bindings[$queueable] = $binding;
        }
    }

    public function withDefault(Closure|string $binding): void
    {
        if ($this->default !== null) {
            throw new InfrastructureException('Default enqueuer is already set.');
        }

        if (is_string($binding) && $this->container === null) {
            throw new InfrastructureException(
                'Cannot bind default enqueuer as a string without a PSR container.',
            );
        }

        $this->default = $binding;
    }

    public function get(string $command): Enqueuer
    {
        $binding = $this->bindings[$command] ?? $this->default;

        if ($binding instanceof Closure) {
            $instance = $binding();
            assert(is_object($instance), "Enqueuer binding for command {$command} must return an object.");
            return new Enqueuer($instance);
        }

        if (is_string($binding)) {
            $instance = $this->container?->get($binding);
            assert(is_object($instance), "PSR container enqueuer binding {$binding} is not an object.");
            return new Enqueuer($instance);
        }

        throw new InfrastructureException('No enqueuer bound for command: ' . $command);
    }
}
