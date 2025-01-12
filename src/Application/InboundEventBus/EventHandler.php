<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\InboundEventBus;

use CloudCreativity\Modules\Contracts\Application\InboundEventBus\EventHandler as IEventHandler;
use CloudCreativity\Modules\Contracts\Application\Messages\DispatchThroughMiddleware;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\IntegrationEvent;

final class EventHandler implements IEventHandler
{
    /**
     * EventHandler constructor.
     *
     * @param object $handler
     */
    public function __construct(private readonly object $handler)
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(IntegrationEvent $event): void
    {
        assert(method_exists($this->handler, 'handle'), sprintf(
            'Cannot dispatch "%s" - handler "%s" does not have a handle method.',
            $event::class,
            $this->handler::class,
        ));

        $this->handler->handle($event);
    }

    /**
     * @inheritDoc
     */
    public function middleware(): array
    {
        if ($this->handler instanceof DispatchThroughMiddleware) {
            return $this->handler->middleware();
        }

        return [];
    }
}
