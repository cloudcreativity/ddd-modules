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

use CloudCreativity\Modules\Contracts\Application\DomainEventDispatching\ListenerContainer as IListenerContainer;
use CloudCreativity\Modules\Contracts\Application\UnitOfWork\UnitOfWorkManager;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;
use CloudCreativity\Modules\Contracts\Domain\Events\OccursImmediately;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

abstract class UnitOfWorkAwareDispatcher extends Dispatcher
{
    public function __construct(
        private readonly UnitOfWorkManager $unitOfWorkManager,
        ContainerInterface|IListenerContainer $listeners = new ListenerContainer(),
        ?PipeContainer $middleware = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
        parent::__construct($listeners, $middleware);
    }

    public function dispatch(DomainEvent $event): void
    {
        if ($event instanceof OccursImmediately) {
            $this->dispatchNow($event);
            return;
        }

        $this->unitOfWorkManager->beforeCommit(function () use ($event): void {
            $this->dispatchNow($event);
        });
    }

    /**
     * Execute the listener or queue it in the unit of work manager.
     */
    protected function execute(DomainEvent $event, EventHandler $listener): void
    {
        if ($listener->beforeCommit()) {
            $this->logger?->debug('Deferring listener to be handled before commit.', [
                'event' => $event::class,
                'listener' => (string) $listener,
            ]);
            $this->unitOfWorkManager->beforeCommit(function () use ($event, $listener): void {
                $this->logger?->debug('Executing listener before commit.', [
                    'event' => $event::class,
                    'listener' => (string) $listener,
                ]);
                $listener($event);
            });
            return;
        }

        if ($listener->afterCommit()) {
            $this->logger?->debug('Deferring listener to be handled after commit.', [
                'event' => $event::class,
                'listener' => (string) $listener,
            ]);
            $this->unitOfWorkManager->afterCommit(function () use ($event, $listener): void {
                $this->logger?->debug('Executing listener after commit.', [
                    'event' => $event::class,
                    'listener' => (string) $listener,
                ]);
                $listener($event);
            });
            return;
        }

        $listener($event);
    }
}
