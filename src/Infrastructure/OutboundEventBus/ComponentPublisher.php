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

use CloudCreativity\Modules\Bus\PsrPipeContainer;
use CloudCreativity\Modules\Contracts\Application\Ports\OutboundEventPublisher;
use CloudCreativity\Modules\Contracts\Infrastructure\OutboundEventBus\PublisherHandlerContainer as IPublisherHandlerContainer;
use CloudCreativity\Modules\Contracts\Messaging\IntegrationEvent;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer as IPipeContainer;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;
use CloudCreativity\Modules\Toolkit\Pipeline\Through;
use Psr\Container\ContainerInterface;
use ReflectionClass;

abstract class ComponentPublisher implements OutboundEventPublisher
{
    private readonly IPublisherHandlerContainer $handlers;

    private readonly ?IPipeContainer $middleware;

    /**
     * @var array<callable|string>
     */
    private array $pipes = [];

    public function __construct(
        ContainerInterface|IPublisherHandlerContainer $handlers,
        ?IPipeContainer $middleware = null,
    ) {
        $this->handlers = $handlers instanceof ContainerInterface ?
            new PublisherHandlerContainer(container: $handlers) :
            $handlers;

        $this->middleware = $middleware === null && $handlers instanceof ContainerInterface ?
            new PsrPipeContainer(container: $handlers) :
            $middleware;

        $this->autowire();
    }

    /**
     * Publish events through the provided pipes.
     *
     * @param list<callable|string> $pipes
     */
    public function through(array $pipes): void
    {
        assert(array_is_list($pipes), 'Expecting an array list of middleware.');

        $this->pipes = $pipes;
    }

    public function publish(IntegrationEvent $event): void
    {
        $pipeline = PipelineBuilder::make($this->middleware)
            ->through($this->pipes)
            ->build(new MiddlewareProcessor(function (IntegrationEvent $passed): void {
                $handler = $this->handlers->get($passed::class);
                $handler($passed);
            }));

        $pipeline->process($event);
    }

    private function autowire(): void
    {
        $reflection = new ReflectionClass($this);

        if ($this->handlers instanceof PublisherHandlerContainer) {
            foreach ($reflection->getAttributes(Publishes::class) as $attribute) {
                $instance = $attribute->newInstance();
                $this->handlers->bind($instance->event, $instance->publisher);
            }

            foreach ($reflection->getAttributes(DefaultPublisher::class) as $attribute) {
                $this->handlers->withDefault($attribute->newInstance()->publisher);
            }
        }

        foreach ($reflection->getAttributes(Through::class) as $attribute) {
            $this->pipes = $attribute->newInstance()->pipes;
        }
    }
}
