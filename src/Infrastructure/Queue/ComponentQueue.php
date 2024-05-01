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

use CloudCreativity\Modules\Contracts\Application\Messages\Command;
use CloudCreativity\Modules\Contracts\Application\Ports\Driven\Queue\Queue;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;

class ComponentQueue implements Queue
{
    /**
     * @var list<string|callable>
     */
    private array $pipes = [];

    /**
     * ComponentQueue constructor.
     *
     * @param EnqueuerContainerInterface $enqueuers
     * @param PipeContainer|null $middleware
     */
    public function __construct(
        private readonly EnqueuerContainerInterface $enqueuers,
        private readonly ?PipeContainer $middleware = null,
    ) {
    }

    /**
     * Dispatch messages through the provided pipes.
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
        $enqueuer = $this->enqueuers->get($command::class);

        $pipeline = PipelineBuilder::make($this->middleware)
            ->through($this->pipes)
            ->build(MiddlewareProcessor::call($enqueuer));

        $pipeline->process($command);
    }
}
