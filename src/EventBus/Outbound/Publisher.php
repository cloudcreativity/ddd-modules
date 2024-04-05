<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\EventBus\Outbound;

use CloudCreativity\Modules\EventBus\IntegrationEventHandlerContainerInterface;
use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;

final class Publisher implements PublisherInterface
{
    /**
     * @var PipelineBuilder
     */
    private readonly PipelineBuilder $pipelineBuilder;

    /**
     * @var array<string|callable>
     */
    private array $pipes = [];

    /**
     * Publisher constructor.
     *
     * @param IntegrationEventHandlerContainerInterface $handlers
     * @param PipeContainerInterface|null $middleware
     */
    public function __construct(
        private readonly IntegrationEventHandlerContainerInterface $handlers,
        ?PipeContainerInterface $middleware = null,
    ) {
        $this->pipelineBuilder = new PipelineBuilder($middleware);
    }

    /**
     * Send outbound integration events through the provided pipes.
     *
     * @param array<string|callable> $pipes
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
    public function publish(IntegrationEventInterface $event): void
    {
        $handler = $this->handlers->get($event::class);

        $pipeline = $this->pipelineBuilder
            ->through([...$this->pipes, ...$handler->middleware()])
            ->build(new MiddlewareProcessor(function (IntegrationEventInterface $passed) use ($handler): void {
                $handler($passed);
            }));

        $pipeline->process($event);
    }
}
