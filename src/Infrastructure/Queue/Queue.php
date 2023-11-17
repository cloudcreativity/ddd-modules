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

namespace CloudCreativity\Modules\Infrastructure\Queue;

use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactory;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactoryInterface;

class Queue implements QueueInterface
{
    /**
     * @var array<string|callable>
     */
    private array $pipes = [];

    /**
     * Queue constructor.
     *
     * @param QueueHandlerContainerInterface $handlers
     * @param PipelineBuilderFactoryInterface $pipelineFactory
     */
    public function __construct(
        private readonly QueueHandlerContainerInterface $handlers,
        private readonly PipelineBuilderFactoryInterface $pipelineFactory = new PipelineBuilderFactory(),
    ) {
    }

    /**
     * Push jobs through the provided pipes when queuing them.
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
    public function push(QueueableInterface $queueable): void
    {
        $this->pushBatch(new QueueableBatch($queueable));
    }

    /**
     * @inheritDoc
     */
    public function pushBatch(QueueableBatch $batch): void
    {
        $handler = $this->handlers->get(
            $batch->first()::class,
        );

        $pipeline = $this->pipelineFactory
            ->getPipelineBuilder()
            ->through([...$this->pipes, ...array_values($handler->middleware())])
            ->build(new MiddlewareProcessor(
                static function (QueueableInterface $message) use ($handler): QueueableInterface {
                    $handler($message);
                    return $message;
                },
            ));

        $handler->withBatch($batch);

        foreach ($batch as $queueable) {
            $pipeline->process($queueable);
        }
    }
}
