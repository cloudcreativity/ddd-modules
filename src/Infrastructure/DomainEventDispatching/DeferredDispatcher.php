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

    /**
     * @inheritDoc
     */
    protected function execute(DomainEventInterface $event, EventHandler $listener): void
    {
        $listener($event);
    }
}
