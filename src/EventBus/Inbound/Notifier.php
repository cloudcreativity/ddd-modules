<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\EventBus\Inbound;

use CloudCreativity\Modules\EventBus\IntegrationEventHandlerContainerInterface;
use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactory;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactoryInterface;

final class Notifier implements NotifierInterface
{
    /**
     * @var PipelineBuilderFactoryInterface
     */
    private readonly PipelineBuilderFactoryInterface $pipelineFactory;

    /**
     * @var array<string|callable>
     */
    private array $pipes = [];

    /**
     * Notifier constructor.
     *
     * @param IntegrationEventHandlerContainerInterface $handlers
     * @param PipelineBuilderFactoryInterface|PipeContainerInterface|null $pipeline
     */
    public function __construct(
        private readonly IntegrationEventHandlerContainerInterface $handlers,
        PipelineBuilderFactoryInterface|PipeContainerInterface|null $pipeline = null,
    ) {
        $this->pipelineFactory = PipelineBuilderFactory::make($pipeline);
    }

    /**
     * Send inbound integration events through the provided pipes.
     *
     * @param array<string|callable> $pipes
     * @return void
     */
    public function through(array $pipes): void
    {
        $this->pipes = array_values($pipes);
    }

    /**
     * @inheritDoc
     */
    public function notify(IntegrationEventInterface $event): void
    {
        $handler = $this->handlers->get($event::class);

        $pipeline = $this->pipelineFactory
            ->getPipelineBuilder()
            ->through([...$this->pipes, ...$handler->middleware()])
            ->build(new MiddlewareProcessor(function (IntegrationEventInterface $passed) use ($handler): void {
                $handler($passed);
            }));

        $pipeline->process($event);
    }
}
