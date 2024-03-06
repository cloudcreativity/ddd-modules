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

use CloudCreativity\Modules\Domain\Events\DomainEventInterface;
use CloudCreativity\Modules\Domain\Events\OccursImmediately;

class DeferredDispatcher extends Dispatcher implements DeferredDispatcherInterface
{
    /**
     * @var array<DomainEventInterface>
     */
    private array $deferred = [];

    /**
     * @inheritDoc
     */
    public function dispatch(DomainEventInterface $event): void
    {
        if ($event instanceof OccursImmediately) {
            $this->dispatchNow($event);
            return;
        }

        $this->deferred[] = $event;
    }

    /**
     * @inheritDoc
     */
    public function flush(): void
    {
        try {
            while ($event = array_shift($this->deferred)) {
                $this->dispatchNow($event);
            }
        } finally {
            $this->deferred = [];
        }
    }

    /**
     * @inheritDoc
     */
    public function forget(): void
    {
        $this->deferred = [];
    }
}
