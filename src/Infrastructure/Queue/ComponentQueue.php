<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Queue;

use CloudCreativity\Modules\Contracts\Application\Ports\Driven\Queue;
use CloudCreativity\Modules\Contracts\Infrastructure\Queue\EnqueuerContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;

class ComponentQueue implements Queue
{
    /**
     * @var list<callable|string>
     */
    private array $pipes = [];

    /**
     * ComponentQueue constructor.
     *
     */
    public function __construct(
        private readonly EnqueuerContainer $enqueuers,
        private readonly ?PipeContainer $middleware = null,
    ) {
    }

    /**
     * Dispatch messages through the provided pipes.
     *
     * @param list<callable|string> $pipes
     */
    public function through(array $pipes): void
    {
        $this->pipes = array_values($pipes);
    }

    public function push(Command $command): void
    {
        $pipeline = PipelineBuilder::make($this->middleware)
            ->through($this->pipes)
            ->build(new MiddlewareProcessor(function (Command $passed): void {
                $enqueuer = $this->enqueuers->get($passed::class);
                $enqueuer($passed);
            }));

        $pipeline->process($command);
    }
}
