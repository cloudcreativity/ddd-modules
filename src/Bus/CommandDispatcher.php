<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus;

use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactory;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactoryInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

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
