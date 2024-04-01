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
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactory;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactoryInterface;

final class ClosureEnqueuer implements EnqueuerInterface
{
    /**
     * @var PipelineBuilderFactoryInterface
     */
    private readonly PipelineBuilderFactoryInterface $pipelineFactory;

    /**
     * @var array<class-string<QueueableInterface>, Closure>
     */
    private array $enqueuers = [];

    /**
     * @var array<string|callable>
     */
    private array $pipes = [];

    /**
     * ClosureEnqueuer constructor.
     *
     * @param Closure(QueueableInterface): void $fn
     */
    public function __construct(
        private readonly Closure $fn,
        PipeContainerInterface|null $pipeline = null,
    ) {
        $this->pipelineFactory = new PipelineBuilderFactory($pipeline);
    }

    /**
     * Register an enqueuer for a specific job.
     *
     * @param class-string<QueueableInterface> $queuable
     * @param Closure $fn
     * @return void
     */
    public function register(string $queuable, Closure $fn): void
    {
        $this->enqueuers[$queuable] = $fn;
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
    public function queue(QueueableInterface $queueable): void
    {
        $enqueuer = $this->enqueuers[$queueable::class] ?? $this->fn;

        $pipeline = $this->pipelineFactory
            ->getPipelineBuilder()
            ->through($this->pipes)
            ->build(new MiddlewareProcessor($enqueuer));

        $pipeline->process($queueable);
    }
}
