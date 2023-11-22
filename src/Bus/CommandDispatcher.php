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

namespace CloudCreativity\Modules\Bus;

use CloudCreativity\Modules\Bus\Results\ResultInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactory;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactoryInterface;

class CommandDispatcher implements CommandDispatcherInterface
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
     * CommandDispatcher constructor.
     *
     * @param CommandHandlerContainerInterface $handlers
     * @param PipelineBuilderFactoryInterface|PipeContainerInterface|null $pipeline
     */
    public function __construct(
        private readonly CommandHandlerContainerInterface $handlers,
        PipelineBuilderFactoryInterface|PipeContainerInterface|null $pipeline = null,
    ) {
        $this->pipelineFactory = PipelineBuilderFactory::make($pipeline);
    }

    /**
     * Dispatch messages through the provided pipes.
     *
     * @param array<string|callable> $pipes
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
    public function dispatch(CommandInterface $command): ResultInterface
    {
        $handler = $this->handlers->get($command::class);

        $pipeline = $this->pipelineFactory
            ->getPipelineBuilder()
            ->through([...$this->pipes, ...array_values($handler->middleware())])
            ->build(MiddlewareProcessor::wrap($handler));

        $result = $pipeline->process($command);

        assert($result instanceof ResultInterface, 'Expecting pipeline to return a result object.');

        return $result;
    }
}
