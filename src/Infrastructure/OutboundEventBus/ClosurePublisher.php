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
use CloudCreativity\Modules\Application\Ports\Driven\OutboundEventBus\EventPublisherInterface;
use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;

class ClosurePublisher implements EventPublisherInterface
{
    /**
     * @var array<class-string<IntegrationEventInterface>, Closure>
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
        private readonly ?PipeContainerInterface $middleware = null,
    ) {
    }

    /**
     * Bind a publisher for the specified event.
     *
     * @param class-string<IntegrationEventInterface> $event
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
    public function publish(IntegrationEventInterface $event): void
    {
        $publisher = $this->bindings[$event::class] ?? $this->fn;

        $pipeline = PipelineBuilder::make($this->middleware)
            ->through($this->pipes)
            ->build(new MiddlewareProcessor($publisher));

        $pipeline->process($event);
    }
}
