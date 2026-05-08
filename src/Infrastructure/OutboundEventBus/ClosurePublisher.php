<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\OutboundEventBus;

use Closure;
use CloudCreativity\Modules\Bus\PsrPipeContainer;
use CloudCreativity\Modules\Contracts\Application\Ports\OutboundEventPublisher;
use CloudCreativity\Modules\Contracts\Messaging\IntegrationEvent;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer as IPipeContainer;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;
use CloudCreativity\Modules\Toolkit\Pipeline\Through;
use Psr\Container\ContainerInterface;
use ReflectionClass;

abstract class ClosurePublisher implements OutboundEventPublisher
{
    private readonly ?IPipeContainer $middleware;

    /**
     * @var array<class-string<IntegrationEvent>, Closure>
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
     * Bind a publisher for the specified event.
     *
     * @param class-string<IntegrationEvent> $event
     */
    public function bind(string $event, Closure $fn): void
    {
        $this->bindings[$event] = $fn;
    }

    /**
     * Publish events through the provided pipes.
     *
     * @param list<callable|string> $pipes
     */
    public function through(array $pipes): void
    {
        $this->pipes = array_values($pipes);
    }

    public function publish(IntegrationEvent $event): void
    {
        $publisher = $this->bindings[$event::class] ?? $this->fn;

        $pipeline = PipelineBuilder::make($this->middleware)
            ->through($this->pipes)
            ->build(new MiddlewareProcessor($publisher));

        $pipeline->process($event);
    }

    private function autowire(): void
    {
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getAttributes(Through::class) as $attribute) {
            $instance = $attribute->newInstance();
            $this->pipes = $instance->pipes;
        }
    }
}
