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

use CloudCreativity\Modules\Bus\PsrPipeContainer;
use CloudCreativity\Modules\Contracts\Application\Ports\Queue;
use CloudCreativity\Modules\Contracts\Infrastructure\Queue\EnqueuerContainer as IEnqueuerContainer;
use CloudCreativity\Modules\Contracts\Messaging\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer as IPipeContainer;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;
use CloudCreativity\Modules\Toolkit\Pipeline\Through;
use Psr\Container\ContainerInterface;
use ReflectionClass;

abstract class ComponentQueue implements Queue
{
    private readonly IEnqueuerContainer $enqueuers;

    private readonly ?IPipeContainer $middleware;

    /**
     * @var list<callable|string>
     */
    private array $pipes = [];

    public function __construct(
        ContainerInterface|IEnqueuerContainer $enqueuers,
        ?IPipeContainer $middleware = null,
    ) {
        $this->enqueuers = $enqueuers instanceof ContainerInterface ?
            new EnqueuerContainer(container: $enqueuers) :
            $enqueuers;

        $this->middleware = $middleware === null && $enqueuers instanceof ContainerInterface
            ? new PsrPipeContainer($enqueuers)
            : $middleware;

        $this->autowire();
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

    private function autowire(): void
    {
        $reflection = new ReflectionClass($this);

        if ($this->enqueuers instanceof EnqueuerContainer) {
            foreach ($reflection->getAttributes(Queues::class) as $attribute) {
                $instance = $attribute->newInstance();
                $this->enqueuers->bind($instance->command, $instance->enqueuer);
            }

            foreach ($reflection->getAttributes(DefaultEnqueuer::class) as $attribute) {
                $this->enqueuers->withDefault($attribute->newInstance()->enqueuer);
            }
        }

        foreach ($reflection->getAttributes(Through::class) as $attribute) {
            $this->pipes = $attribute->newInstance()->pipes;
        }
    }
}
