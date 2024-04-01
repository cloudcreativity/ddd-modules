<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus\Queue;

use Closure;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactory;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactoryInterface;

final class ClosureEnqueuer implements CommandEnqueuerInterface
{
    /**
     * @var PipelineBuilderFactoryInterface
     */
    private readonly PipelineBuilderFactoryInterface $pipelineFactory;

    /**
     * @var array<class-string<CommandInterface>, Closure>
     */
    private array $enqueuers = [];

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
        PipeContainerInterface|null $middleware = null,
    ) {
        $this->pipelineFactory = new PipelineBuilderFactory($middleware);
    }

    /**
     * Register an enqueuer for a specific command.
     *
     * @param class-string<CommandInterface> $command
     * @param Closure $fn
     * @return void
     */
    public function register(string $command, Closure $fn): void
    {
        $this->enqueuers[$command] = $fn;
    }

    /**
     * Queue commands through the provided pipes.
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
    public function queue(CommandInterface $command): void
    {
        $enqueuer = $this->enqueuers[$command::class] ?? $this->fn;

        $pipeline = $this->pipelineFactory
            ->getPipelineBuilder()
            ->through($this->pipes)
            ->build(new MiddlewareProcessor($enqueuer));

        $pipeline->process($command);
    }
}
