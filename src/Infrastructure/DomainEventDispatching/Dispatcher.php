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

namespace CloudCreativity\Modules\Infrastructure\DomainEventDispatching;

use Closure;
use CloudCreativity\Modules\Domain\Events\DomainEventInterface;
use CloudCreativity\Modules\Domain\Events\OccursImmediately;
use CloudCreativity\Modules\Infrastructure\Persistence\UnitOfWorkManagerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactory;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactoryInterface;
use Generator;
use InvalidArgumentException;

class Dispatcher implements DispatcherInterface
{
    /**
     * @var PipelineBuilderFactoryInterface
     */
    private readonly PipelineBuilderFactoryInterface $pipelineFactory;

    /**
     * @var array<string, array<string|callable>>
     */
    private array $bindings = [];

    /**
     * @var array<string|callable>
     */
    private array $pipes = [];

    /**
     * Dispatcher constructor.
     *
     * @param ListenerContainerInterface $listeners
     * @param UnitOfWorkManagerInterface $unitOfWorkManager
     * @param PipelineBuilderFactoryInterface|PipeContainerInterface|null $pipeline
     */
    public function __construct(
        private readonly ListenerContainerInterface $listeners,
        private readonly UnitOfWorkManagerInterface $unitOfWorkManager,
        PipelineBuilderFactoryInterface|PipeContainerInterface|null $pipeline = null,
    ) {
        $this->pipelineFactory = PipelineBuilderFactory::make($pipeline);
    }

    /**
     * Dispatch events through the provided pipes.
     *
     * @param array<string|callable> $pipes
     * @return void
     */
    public function through(array $pipes): void
    {
        assert(array_is_list($pipes));

        $this->pipes = $pipes;
    }

    /**
     * @inheritDoc
     */
    public function listen(string $event, string|Closure|array $listener): void
    {
        $bindings = $this->bindings[$event] ?? [];

        foreach ((array) $listener as $name) {
            if ($this->canAttach($name)) {
                $bindings[] = $name;
                continue;
            }

            throw new InvalidArgumentException('Expecting listener to be a Closure or non-empty string.');
        }

        $this->bindings[$event] = $bindings;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(DomainEventInterface $event): void
    {
        if ($event instanceof OccursImmediately) {
            $this->dispatchNow($event);
            return;
        }

        $this->unitOfWorkManager->afterCommit(function () use ($event): void {
            $this->dispatchNow($event);
        });
    }

    /**
     * Dispatch the event now.
     *
     * @param DomainEventInterface $event
     * @return void
     */
    private function dispatchNow(DomainEventInterface $event): void
    {
        $pipeline = $this->pipelineFactory
            ->getPipelineBuilder()
            ->through($this->pipes)
            ->build(new MiddlewareProcessor($this->dispatcher()));

        $pipeline->process($event);
    }

    /**
     * Is the provided listener valid to attach to an event?
     *
     * @param mixed $listener
     * @return bool
     */
    private function canAttach(mixed $listener): bool
    {
        if ($listener instanceof Closure) {
            return true;
        }

        return is_string($listener) && !empty($listener);
    }

    /**
     * @return Closure
     */
    private function dispatcher(): Closure
    {
        return function (DomainEventInterface $event): DomainEventInterface {
            foreach ($this->cursor($event::class) as $listener) {
                $this->dispatchOrQueue($event, $listener);
            }
            return $event;
        };
    }

    /**
     * Execute the listener or queue it in the transaction manager.
     *
     * @param DomainEventInterface $event
     * @param EventHandler $listener
     * @return void
     */
    private function dispatchOrQueue(DomainEventInterface $event, EventHandler $listener): void
    {
        if ($listener->beforeCommit()) {
            $this->unitOfWorkManager->beforeCommit(static function () use ($event, $listener): void {
                $listener($event);
            });
            return;
        }

        if ($listener->afterCommit()) {
            $this->unitOfWorkManager->afterCommit(static function () use ($event, $listener): void {
                $listener($event);
            });
            return;
        }

        $listener($event);
    }

    /**
     * Get a cursor to iterate through all listeners for the event.
     *
     * @param string $eventName
     * @return Generator<EventHandler>
     */
    private function cursor(string $eventName): Generator
    {
        foreach ($this->bindings[$eventName] ?? [] as $listener) {
            if (is_string($listener)) {
                $listener = $this->listeners->get($listener);
            }

            assert(is_object($listener), 'Expecting listener to be an object.');

            yield new EventHandler($listener);
        }
    }
}
