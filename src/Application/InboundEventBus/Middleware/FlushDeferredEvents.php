<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\InboundEventBus\Middleware;

use Closure;
use CloudCreativity\Modules\Application\DomainEventDispatching\DeferredDispatcherInterface;
use CloudCreativity\Modules\Application\Messages\IntegrationEventInterface;
use Throwable;

final class FlushDeferredEvents implements InboundEventMiddlewareInterface
{
    /**
     * FlushDeferredEvents constructor.
     *
     * @param DeferredDispatcherInterface $dispatcher
     */
    public function __construct(private readonly DeferredDispatcherInterface $dispatcher)
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(IntegrationEventInterface $event, Closure $next): void
    {
        try {
            $next($event);
        } catch (Throwable $ex) {
            $this->dispatcher->forget();
            throw $ex;
        }

        $this->dispatcher->flush();
    }
}
