<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\InboundEventBus;

use CloudCreativity\Modules\Application\Messages\DispatchThroughMiddleware;
use CloudCreativity\Modules\Application\Messages\IntegrationEventInterface;

final class EventHandler implements EventHandlerInterface
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
    public function __invoke(IntegrationEventInterface $event): void
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
