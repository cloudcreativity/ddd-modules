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

use Closure;
use CloudCreativity\Modules\Contracts\Application\Bus\CommandHandlerContainer;
use CloudCreativity\Modules\Contracts\Application\Messages\Command;
use CloudCreativity\Modules\Contracts\Application\Ports\Driven\Queue\Queue;
use CloudCreativity\Modules\Contracts\Application\Ports\Driving\Commands\CommandDispatcher as ICommandDispatcher;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;
use RuntimeException;

class CommandDispatcher implements ICommandDispatcher
{
    /**
     * @var null|Queue|Closure(): Queue
     */
    private Queue|Closure|null $queue;

    /**
     * @var array<string|callable>
     */
    private array $pipes = [];

    /**
     * CommandDispatcher constructor.
     *
     * @param CommandHandlerContainer $handlers
     * @param PipeContainer|null $middleware
     * @param null|Closure(): Queue $queue
     */
    public function __construct(
        private readonly CommandHandlerContainer $handlers,
        private readonly ?PipeContainer $middleware = null,
        ?Closure $queue = null,
    ) {
        $this->queue = $queue;
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
        $handler = $this->handlers->get($command::class);

        $pipeline = PipelineBuilder::make($this->middleware)
            ->through([...$this->pipes, ...array_values($handler->middleware())])
            ->build(MiddlewareProcessor::wrap($handler));

        $result = $pipeline->process($command);

        assert($result instanceof Result, 'Expecting pipeline to return a result object.');

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function queue(Command $command): void
    {
        if ($this->queue === null) {
            throw new RuntimeException(
                'Commands cannot be queued because the command dispatcher has not been given a queue factory.',
            );
        }

        if ($this->queue instanceof Closure) {
            $this->queue = ($this->queue)();
        }

        $this->queue->push($command);
    }
}
