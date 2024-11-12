<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\DomainEventDispatching;

use Closure;
use CloudCreativity\Modules\Contracts\Application\DomainEventDispatching\ListenerContainer as IListenerContainer;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEventDispatcher;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;
use Generator;
use InvalidArgumentException;

class Dispatcher implements DomainEventDispatcher
{
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
     * @param IListenerContainer $listeners
     * @param PipeContainer|null $middleware
     */
    public function __construct(
        private readonly IListenerContainer $listeners = new ListenerContainer(),
        private readonly ?PipeContainer $middleware = null,
    ) {
    }

    /**
     * Dispatch events through the provided pipes.
     *
     * @param array<string|callable> $pipes
     * @return void
     */
    public function through(array $pipes): void
    {
        assert(array_is_list($pipes), 'Expecting a list of middleware.');

        $this->pipes = $pipes;
    }

    /**
     * @param string $event
     * @param string|Closure|list<string|Closure> $listener
     * @return void
     */
    public function listen(string $event, string|Closure|array $listener): void
    {
        $bindings = $this->bindings[$event] ?? [];

        foreach (is_array($listener) ? $listener : [$listener] as $name) {
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
    public function dispatch(DomainEvent $event): void
    {
        $this->dispatchNow($event);
    }

    /**
     * Dispatch the events immediately.
     *
     * @param DomainEvent $event
     * @return void
     */
    protected function dispatchNow(DomainEvent $event): void
    {
        $pipeline = PipelineBuilder::make($this->middleware)
            ->through($this->pipes)
            ->build(new MiddlewareProcessor($this->dispatcher()));

        $pipeline->process($event);
    }

    /**
     * @return Closure
     */
    private function dispatcher(): Closure
    {
        return function (DomainEvent $event): DomainEvent {
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
     * @param DomainEvent $event
     * @param EventHandler $listener
     * @return void
     */
    protected function execute(DomainEvent $event, EventHandler $listener): void
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
