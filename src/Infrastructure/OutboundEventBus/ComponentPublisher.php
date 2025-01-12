<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\OutboundEventBus;

use CloudCreativity\Modules\Contracts\Application\Ports\Driven\OutboundEventPublisher;
use CloudCreativity\Modules\Contracts\Infrastructure\OutboundEventBus\PublisherHandlerContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\IntegrationEvent;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;

class ComponentPublisher implements OutboundEventPublisher
{
    /**
     * @var array<string|callable>
     */
    private array $pipes = [];

    /**
     * ComponentPublisher constructor.
     *
     * @param PublisherHandlerContainer $handlers
     * @param PipeContainer|null $middleware
     */
    public function __construct(
        private readonly PublisherHandlerContainer $handlers,
        private readonly ?PipeContainer $middleware = null,
    ) {
    }

    /**
     * Publish events through the provided pipes.
     *
     * @param list<string|callable> $pipes
     * @return void
     */
    public function through(array $pipes): void
    {
        assert(array_is_list($pipes), 'Expecting an array list of middleware.');

        $this->pipes = $pipes;
    }

    /**
     * @inheritDoc
     */
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
}
