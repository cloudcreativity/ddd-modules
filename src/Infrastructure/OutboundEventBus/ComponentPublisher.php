<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\OutboundEventBus;

use CloudCreativity\Modules\Contracts\Application\Messages\IntegrationEvent;
use CloudCreativity\Modules\Contracts\Application\Ports\Driven\OutboundEventBus\EventPublisher;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;

class ComponentPublisher implements EventPublisher
{
    /**
     * @var array<string|callable>
     */
    private array $pipes = [];

    /**
     * ComponentPublisher constructor.
     *
     * @param PublisherHandlerContainerInterface $handlers
     * @param PipeContainer|null $middleware
     */
    public function __construct(
        private readonly PublisherHandlerContainerInterface $handlers,
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

        $this->pipes = array_values($pipes);
    }

    /**
     * @inheritDoc
     */
    public function publish(IntegrationEvent $event): void
    {
        $handler = $this->handlers->get($event::class);

        $pipeline = PipelineBuilder::make($this->middleware)
            ->through($this->pipes)
            ->build(MiddlewareProcessor::call($handler));

        $pipeline->process($event);
    }
}
