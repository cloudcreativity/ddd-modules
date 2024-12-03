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
use CloudCreativity\Modules\Contracts\Application\Messages\Command;
use CloudCreativity\Modules\Contracts\Application\Ports\Driven\Queue;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;

class ClosureQueue implements Queue
{
    /**
     * @var array<class-string<Command>, Closure>
     */
    private array $bindings = [];

    /**
     * @var list<string|callable>
     */
    private array $pipes = [];

    /**
     * ClosureQueue constructor.
     *
     * @param Closure $fn
     */
    public function __construct(
        private readonly Closure $fn,
        private readonly ?PipeContainer $middleware = null,
    ) {
    }

    /**
     * Bind an enqueuer for the specified command.
     *
     * @param class-string<Command> $command
     * @param Closure $fn
     * @return void
     */
    public function bind(string $command, Closure $fn): void
    {
        $this->bindings[$command] = $fn;
    }

    /**
     * Queue commands through the provided pipes.
     *
     * @param list<string|callable> $pipes
     * @return void
     */
    public function through(array $pipes): void
    {
        $this->pipes = array_values($pipes);
    }

    /**
     * @inheritDoc
     */
    public function push(Command $command): void
    {
        $enqueuer = $this->bindings[$command::class] ?? $this->fn;

        $pipeline = PipelineBuilder::make($this->middleware)
            ->through($this->pipes)
            ->build(new MiddlewareProcessor($enqueuer));

        $pipeline->process($command);
    }
}
