<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Infrastructure\DomainEventDispatching;

use Closure;
use CloudCreativity\BalancedEvent\Common\Domain\Events\DomainEventInterface;
use CloudCreativity\BalancedEvent\Common\Domain\Events\OccursImmediately;
use CloudCreativity\BalancedEvent\Common\Infrastructure\Persistence\UnitOfWorkManagerInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\MiddlewareProcessor;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipelineBuilderFactory;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipelineBuilderFactoryInterface;
use Generator;
use InvalidArgumentException;

class Dispatcher implements DispatcherInterface
{
    /**
     * @var array
     */
    private array $bindings = [];

    /**
     * @var array
     */
    private array $pipes = [];

    /**
     * Dispatcher constructor.
     *
     * @param ListenerContainerInterface $listeners
     * @param UnitOfWorkManagerInterface $unitOfWorkManager
     * @param PipelineBuilderFactoryInterface $pipelineFactory
     */
    public function __construct(
        private readonly ListenerContainerInterface $listeners,
        private readonly UnitOfWorkManagerInterface $unitOfWorkManager,
        private readonly PipelineBuilderFactoryInterface $pipelineFactory = new PipelineBuilderFactory(),
    ) {
    }

    /**
     * Dispatch events through the provided pipes.
     *
     * @param array $pipes
     * @return void
     */
    public function through(array $pipes): void
    {
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
     * @return Generator
     */
    private function cursor(string $eventName): Generator
    {
        foreach ($this->bindings[$eventName] ?? [] as $listener) {
            if (is_string($listener)) {
                $listener = $this->listeners->get($listener);
            }

            yield new EventHandler($listener);
        }
    }
}
