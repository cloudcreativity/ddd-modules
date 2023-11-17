<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
