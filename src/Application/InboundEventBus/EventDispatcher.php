<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\InboundEventBus;

use CloudCreativity\Modules\Application\Ports\Driving\InboundEvents\EventDispatcherInterface;
use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;

class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var array<string|callable>
     */
    private array $pipes = [];

    /**
     * EventDispatcher constructor.
     *
     * @param EventHandlerContainerInterface $handlers
     * @param PipeContainerInterface|null $middleware
     */
    public function __construct(
        private readonly EventHandlerContainerInterface $handlers,
        private readonly ?PipeContainerInterface $middleware = null,
    ) {
    }

    /**
     * Dispatch events through the provided pipes.
     *
     * @param list<string|callable> $pipes
     * @return void
     */
    public function through(array $pipes): void
    {
        assert(array_is_list($pipes), 'Expecting a list of pipes.');

        $this->pipes = $pipes;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(IntegrationEventInterface $event): void
    {
        $handler = $this->handlers->get($event::class);

        $pipeline = PipelineBuilder::make($this->middleware)
            ->through([...$this->pipes, ...$handler->middleware()])
            ->build(MiddlewareProcessor::call($handler));

        $pipeline->process($event);
    }
}
