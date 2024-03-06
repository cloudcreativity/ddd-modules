<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\DomainEventDispatching;

use Closure;
use CloudCreativity\Modules\Domain\Events\DomainEventInterface;
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
     * @param PipelineBuilderFactoryInterface|PipeContainerInterface|null $pipeline
     */
    public function __construct(
        private readonly ListenerContainerInterface $listeners,
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
        $this->dispatchNow($event);
    }

    /**
     * Dispatch the events immediately.
     *
     * @param DomainEventInterface $event
     * @return void
     */
    protected function dispatchNow(DomainEventInterface $event): void
    {
        $pipeline = $this->pipelineFactory
            ->getPipelineBuilder()
            ->through($this->pipes)
            ->build(new MiddlewareProcessor($this->dispatcher()));

        $pipeline->process($event);
    }

    /**
     * @return Closure
     */
    private function dispatcher(): Closure
    {
        return function (DomainEventInterface $event): DomainEventInterface {
            foreach ($this->cursor($event::class) as $listener) {
                $this->execute($event, $listener);
            }
            return $event;
        };
    }

    /**
     * Get a cursor to iterate through all listeners for the event.
     *
     * @param string $eventName
     * @return Generator<EventHandler>
     */
    protected function cursor(string $eventName): Generator
    {
        foreach ($this->bindings[$eventName] ?? [] as $listener) {
            if (is_string($listener)) {
                $listener = $this->listeners->get($listener);
            }

            assert(is_object($listener), 'Expecting listener to be an object.');

            yield new EventHandler($listener);
        }
    }

    /**
     * Execute the listener.
     *
     * @param DomainEventInterface $event
     * @param EventHandler $listener
     * @return void
     */
    protected function execute(DomainEventInterface $event, EventHandler $listener): void
    {
        $listener($event);
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
}
