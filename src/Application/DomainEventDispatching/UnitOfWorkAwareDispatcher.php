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

use CloudCreativity\Modules\Application\UnitOfWork\UnitOfWorkManagerInterface;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;
use CloudCreativity\Modules\Contracts\Domain\Events\OccursImmediately;

class UnitOfWorkAwareDispatcher extends Dispatcher implements DispatcherInterface
{
    /**
     * UnitOfWorkAwareDispatcher constructor.
     *
     * @param UnitOfWorkManagerInterface $unitOfWorkManager
     * @param ListenerContainerInterface $listeners
     * @param PipeContainer|null $middleware
     */
    public function __construct(
        private readonly UnitOfWorkManagerInterface $unitOfWorkManager,
        ListenerContainerInterface $listeners = new ListenerContainer(),
        ?PipeContainer $middleware = null,
    ) {
        parent::__construct($listeners, $middleware);
    }

    /**
     * @inheritDoc
     */
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
     *
     * @param DomainEvent $event
     * @param EventHandler $listener
     * @return void
     */
    protected function execute(DomainEvent $event, EventHandler $listener): void
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
}
