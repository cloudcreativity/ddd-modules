<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus;

use CloudCreativity\Modules\Contracts\Application\Bus\CommandHandlerContainer;
use CloudCreativity\Modules\Contracts\Application\Ports\Driving\CommandDispatcher as ICommandDispatcher;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;

class CommandDispatcher implements ICommandDispatcher
{
    /**
     * @var array<string|callable>
     */
    private array $pipes = [];

    /**
     * CommandDispatcher constructor.
     *
     * @param CommandHandlerContainer $handlers
     * @param PipeContainer|null $middleware
     */
    public function __construct(
        private readonly CommandHandlerContainer $handlers,
        private readonly ?PipeContainer $middleware = null,
    ) {
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
    public function dispatch(Command $command): Result
    {
        $pipeline = PipelineBuilder::make($this->middleware)
            ->through($this->pipes)
            ->build(new MiddlewareProcessor(
                fn (Command $passed): Result => $this->execute($passed),
            ));

        $result = $pipeline->process($command);

        assert($result instanceof Result, 'Expecting pipeline to return a result object.');

        return $result;
    }

    /**
     * @param Command $command
     * @return Result<mixed>
     */
    private function execute(Command $command): Result
    {
        $handler = $this->handlers->get($command::class);

        $pipeline = PipelineBuilder::make($this->middleware)
            ->through($handler->middleware())
            ->build(MiddlewareProcessor::wrap($handler));

        $result = $pipeline->process($command);

        assert($result instanceof Result, 'Expecting pipeline to return a result object.');

        return $result;
    }
}
