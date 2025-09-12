<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\InboundEventBus;

use CloudCreativity\Modules\Contracts\Application\InboundEventBus\EventHandlerContainer;
use CloudCreativity\Modules\Contracts\Application\Ports\Driving\InboundEventDispatcher as IInboundEventDispatcher;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\IntegrationEvent;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;

class InboundEventDispatcher implements IInboundEventDispatcher
{
    /**
     * @var array<callable|string>
     */
    private array $pipes = [];

    public function __construct(
        private readonly EventHandlerContainer $handlers,
        private readonly ?PipeContainer $middleware = null,
    ) {
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
}
