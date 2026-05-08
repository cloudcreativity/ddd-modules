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
use CloudCreativity\Modules\Bus\PsrPipeContainer;
use CloudCreativity\Modules\Contracts\Application\Ports\Queue;
use CloudCreativity\Modules\Contracts\Messaging\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer as IPipeContainer;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;
use CloudCreativity\Modules\Toolkit\Pipeline\Through;
use Psr\Container\ContainerInterface;
use ReflectionClass;

abstract class ClosureQueue implements Queue
{
    private readonly ?IPipeContainer $middleware;

    /**
     * @var array<class-string<Command>, Closure>
     */
    private array $bindings = [];

    /**
     * @var list<callable|string>
     */
    private array $pipes = [];

    public function __construct(
        private readonly Closure $fn,
        ContainerInterface|IPipeContainer|null $middleware = null,
    ) {
        $this->middleware = $middleware instanceof ContainerInterface ?
            new PsrPipeContainer($middleware) : $middleware;

        $this->autowire();
    }

    /**
     * Bind an enqueuer for the specified command.
     *
     * @param class-string<Command> $command
     */
    public function bind(string $command, Closure $fn): void
    {
        $this->bindings[$command] = $fn;
    }

    /**
     * Queue commands through the provided pipes.
     *
     * @param list<callable|string> $pipes
     */
    public function through(array $pipes): void
    {
        $this->pipes = array_values($pipes);
    }

    public function push(Command $command): void
    {
        $enqueuer = $this->bindings[$command::class] ?? $this->fn;

        $pipeline = PipelineBuilder::make($this->middleware)
            ->through($this->pipes)
            ->build(new MiddlewareProcessor($enqueuer));

        $pipeline->process($command);
    }

    private function autowire(): void
    {
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getAttributes(Through::class) as $attribute) {
            $this->pipes = $attribute->newInstance()->pipes;
        }
    }
}
