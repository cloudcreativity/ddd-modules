<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus;

use CloudCreativity\Modules\Contracts\Bus\EventHandlerContainer as IEventHandlerContainer;
use CloudCreativity\Modules\Contracts\Messaging\InboundEventDispatcher as IInboundEventDispatcher;
use CloudCreativity\Modules\Contracts\Messaging\IntegrationEvent;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer as IPipeContainer;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;
use CloudCreativity\Modules\Toolkit\Pipeline\Through;
use Psr\Container\ContainerInterface;

abstract class InboundEventDispatcher implements IInboundEventDispatcher
{
    private readonly IEventHandlerContainer $handlers;

    private readonly ?IPipeContainer $middleware;

    /**
     * @var array<callable|string>
     */
    private array $pipes = [];

    public function __construct(
        ContainerInterface|IEventHandlerContainer $handlers,
        ?IPipeContainer $middleware = null,
    ) {
        $this->handlers = $handlers instanceof ContainerInterface ?
            new EventHandlerContainer(container: $handlers) :
            $handlers;

        $this->middleware = $middleware === null && $handlers instanceof ContainerInterface ?
            new PsrPipeContainer(container: $handlers) :
            $middleware;

        $this->autowire();
    }

    /**
     * Dispatch events through the provided pipes.
     *
     * @param list<callable|string> $pipes
     */
    public function through(array $pipes): void
    {
        assert(array_is_list($pipes), 'Expecting a list of pipes.');

        $this->pipes = $pipes;
    }

    public function dispatch(IntegrationEvent $event): void
    {
        $pipeline = PipelineBuilder::make($this->middleware)
            ->through($this->pipes)
            ->build(new MiddlewareProcessor(function (IntegrationEvent $passed): void {
                $this->execute($passed);
            }));

        $pipeline->process($event);
    }

    private function execute(IntegrationEvent $event): void
    {
        $handler = $this->handlers->get($event::class);

        $pipeline = PipelineBuilder::make($this->middleware)
            ->through($handler->middleware())
            ->build(MiddlewareProcessor::call($handler));

        $pipeline->process($event);
    }

    private function autowire(): void
    {
        $reflection = new \ReflectionClass($this);

        if ($this->handlers instanceof EventHandlerContainer) {
            foreach ($reflection->getAttributes(WithEvent::class) as $attribute) {
                $instance = $attribute->newInstance();
                $this->handlers->bind($instance->event, $instance->handler);
            }

            foreach ($reflection->getAttributes(WithDefault::class) as $attribute) {
                $instance = $attribute->newInstance();
                $this->handlers->withDefault($instance->handler);
            }
        }

        foreach ($reflection->getAttributes(Through::class) as $attribute) {
            $instance = $attribute->newInstance();
            $this->pipes = $instance->pipes;
        }
    }
}
