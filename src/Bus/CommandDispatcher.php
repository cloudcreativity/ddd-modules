<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Bus;

use CloudCreativity\BalancedEvent\Common\Bus\Results\ResultInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipelineBuilderFactory;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipelineBuilderFactoryInterface;

class CommandDispatcher implements CommandDispatcherInterface
{
    /**
     * @var array
     */
    private array $pipes = [];

    /**
     * CommandDispatcher constructor.
     *
     * @param CommandHandlerContainerInterface $handlers
     * @param PipelineBuilderFactoryInterface $pipelineFactory
     */
    public function __construct(
        private readonly CommandHandlerContainerInterface $handlers,
        private readonly PipelineBuilderFactoryInterface $pipelineFactory = new PipelineBuilderFactory(),
    ) {
    }

    /**
     * Dispatch messages through the provided pipes.
     *
     * @param array $pipes
     * @return void
     */
    public function through(array $pipes): void
    {
        $this->pipes = array_values($pipes);
    }

    /**
     * @inheritDoc
     */
    public function dispatch(CommandInterface $command): ResultInterface
    {
        $handler = $this->handlers->get($command::class);

        $pipeline = $this->pipelineFactory
            ->getPipelineBuilder()
            ->through([...$this->pipes, ...array_values($handler->middleware())])
            ->build(MiddlewareProcessor::wrap($handler));

        return $pipeline->process($command);
    }
}
