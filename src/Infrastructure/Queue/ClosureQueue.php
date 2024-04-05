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
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;

final class ClosureQueue implements QueueInterface
{
    /**
     * @var array<class-string<CommandInterface>, Closure>
     */
    private array $bindings = [];

    /**
     * @var array<string|callable>
     */
    private array $pipes = [];

    /**
     * ClosureEnqueuer constructor.
     *
     * @param Closure(CommandInterface): void $fn
     */
    public function __construct(
        private readonly Closure $fn,
        private readonly ?PipeContainerInterface $middleware = null,
    ) {
    }

    /**
     * Register an enqueuer for the specified command.
     *
     * @param class-string<CommandInterface> $command
     * @param Closure $fn
     * @return void
     */
    public function bind(string $command, Closure $fn): void
    {
        $this->bindings[$command] = $fn;
    }

    /**
     * Queue messages through the provided pipes.
     *
     * @param array<string|callable> $pipes
     * @return void
     */
    public function through(array $pipes): void
    {
        $this->pipes = array_values($pipes);
    }

    /**
     * @inheritDoc
     */
    public function push(CommandInterface|iterable $command): void
    {
        $commands = ($command instanceof CommandInterface) ? [$command] : $command;

        $pipelineBuilder = PipelineBuilder::make($this->middleware)
            ->through($this->pipes);

        foreach ($commands as $cmd) {
            $enqueuer = $this->bindings[$cmd::class] ?? $this->fn;

            $pipelineBuilder
                ->build(new MiddlewareProcessor($enqueuer))
                ->process($cmd);
        }
    }
}
