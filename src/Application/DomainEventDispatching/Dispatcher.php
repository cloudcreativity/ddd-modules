<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\DomainEventDispatching;

use Closure;
use CloudCreativity\Modules\Bus\PsrPipeContainer;
use CloudCreativity\Modules\Contracts\Application\DomainEventDispatching\ListenerContainer as IListenerContainer;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEventDispatcher;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer as IPipeContainer;
use CloudCreativity\Modules\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;
use CloudCreativity\Modules\Toolkit\Pipeline\Through;
use Generator;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use ReflectionClass;

abstract class Dispatcher implements DomainEventDispatcher
{
    private readonly IListenerContainer $listeners;

    private readonly ?IPipeContainer $middleware;

    /**
     * @var array<string, array<callable|string>>
     */
    private array $bindings = [];

    /**
     * @var array<callable|string>
     */
    private array $pipes = [];

    public function __construct(
        ContainerInterface|IListenerContainer $listeners = new ListenerContainer(),
        ?IPipeContainer $middleware = null,
    ) {
        $this->listeners = $listeners instanceof ContainerInterface ?
            new ListenerContainer($listeners) :
            $listeners;

        $this->middleware = $middleware === null && $listeners instanceof ContainerInterface ?
            new PsrPipeContainer($listeners) :
            $middleware;

        $this->autowire();
    }

    /**
     * Dispatch events through the provided pipes.
     *
     * @param array<callable|string> $pipes
     */
    public function through(array $pipes): void
    {
        assert(array_is_list($pipes), 'Expecting a list of middleware.');

        $this->pipes = $pipes;
    }

    /**
     * @param Closure|list<Closure|string>|string $listener
     */
    public function listen(string $event, array|Closure|string $listener): void
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

    public function dispatch(DomainEvent $event): void
    {
        $this->dispatchNow($event);
    }

    /**
     * Dispatch the events immediately.
     */
    protected function dispatchNow(DomainEvent $event): void
    {
        $pipeline = PipelineBuilder::make($this->middleware)
            ->through($this->pipes)
            ->build(new MiddlewareProcessor($this->dispatcher()));

        $pipeline->process($event);
    }

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
     */
    protected function execute(DomainEvent $event, EventHandler $listener): void
    {
        $listener($event);
    }

    /**
     * Is the provided listener valid to attach to an event?
     */
    private function canAttach(mixed $listener): bool
    {
        if ($listener instanceof Closure) {
            return true;
        }

        return is_string($listener) && !empty($listener);
    }

    private function autowire(): void
    {
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getAttributes(ListenTo::class) as $attribute) {
            $instance = $attribute->newInstance();
            $this->listen($instance->event, $instance->listeners);
        }

        foreach ($reflection->getAttributes(Through::class) as $attribute) {
            $this->pipes = $attribute->newInstance()->pipes;
        }
    }
}
