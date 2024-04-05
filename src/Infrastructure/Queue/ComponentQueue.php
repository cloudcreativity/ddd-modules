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

use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;

class ComponentQueue implements QueueInterface
{
    /**
     * @var array<string|callable>
     */
    private array $pipes = [];

    /**
     * ComponentQueue constructor.
     *
     * @param EnqueuerContainerInterface $enqueuers
     * @param PipeContainerInterface|null $middleware
     */
    public function __construct(
        private readonly EnqueuerContainerInterface $enqueuers,
        private readonly ?PipeContainerInterface $middleware = null,
    ) {
    }

    /**
     * Dispatch messages through the provided pipes.
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

        $builder = PipelineBuilder::make($this->middleware)
            ->through($this->pipes);

        foreach ($commands as $cmd) {
            $enqueuer = $this->enqueuers->get($cmd::class);

            $pipeline = $builder->build(MiddlewareProcessor::call($enqueuer));
            $pipeline->process($cmd);
        }
    }
}
