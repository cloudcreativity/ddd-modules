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

use Closure;
use CloudCreativity\Modules\Infrastructure\Queue\QueueInterface;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;
use RuntimeException;

class CommandDispatcher implements CommandDispatcherInterface
{
    /**
     * @var null|QueueInterface|Closure(): QueueInterface
     */
    private QueueInterface|Closure|null $queue;

    /**
     * @var array<string|callable>
     */
    private array $pipes = [];

    /**
     * CommandDispatcher constructor.
     *
     * @param CommandHandlerContainerInterface $handlers
     * @param PipeContainerInterface|null $middleware
     * @param null|Closure(): QueueInterface $queue
     */
    public function __construct(
        private readonly CommandHandlerContainerInterface $handlers,
        private readonly ?PipeContainerInterface $middleware = null,
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
    public function dispatch(CommandInterface $command): ResultInterface
    {
        $handler = $this->handlers->get($command::class);

        $pipeline = PipelineBuilder::make($this->middleware)
            ->through([...$this->pipes, ...array_values($handler->middleware())])
            ->build(MiddlewareProcessor::wrap($handler));

        $result = $pipeline->process($command);

        assert($result instanceof ResultInterface, 'Expecting pipeline to return a result object.');

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function queue(CommandInterface|iterable $command): void
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
