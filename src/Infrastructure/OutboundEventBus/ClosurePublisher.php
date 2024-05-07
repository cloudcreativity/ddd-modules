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

use Closure;
use CloudCreativity\Modules\Contracts\Application\Messages\IntegrationEvent;
use CloudCreativity\Modules\Contracts\Application\Ports\Driven\OutboundEventBus\EventPublisher;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;

class ClosurePublisher implements EventPublisher
{
    /**
     * @var array<class-string<IntegrationEvent>, Closure>
     */
    private array $bindings = [];

    /**
     * @var list<string|callable>
     */
    private array $pipes = [];

    /**
     * ClosurePublisher constructor.
     *
     * @param Closure $fn
     */
    public function __construct(
        private readonly Closure $fn,
        private readonly ?PipeContainer $middleware = null,
    ) {
    }

    /**
     * Bind a publisher for the specified event.
     *
     * @param class-string<IntegrationEvent> $event
     * @param Closure $fn
     * @return void
     */
    public function bind(string $event, Closure $fn): void
    {
        $this->bindings[$event] = $fn;
    }

    /**
     * Publish events through the provided pipes.
     *
     * @param list<string|callable> $pipes
     * @return void
     */
    public function through(array $pipes): void
    {
        $this->pipes = array_values($pipes);
    }

    /**
     * @inheritDoc
     */
    public function publish(IntegrationEvent $event): void
    {
        $publisher = $this->bindings[$event::class] ?? $this->fn;

        $pipeline = PipelineBuilder::make($this->middleware)
            ->through($this->pipes)
            ->build(new MiddlewareProcessor($publisher));

        $pipeline->process($event);
    }
}
