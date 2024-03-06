<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\EventBus;

use Closure;
use CloudCreativity\Modules\Toolkit\Messages\DispatchThroughMiddleware;
use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;

final class IntegrationEventHandler implements IntegrationEventHandlerInterface
{
    /**
     * IntegrationEventHandler constructor.
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
        if ($this->handler instanceof Closure) {
            ($this->handler)($event);
            return;
        }

        assert(method_exists($this->handler, 'handle'), sprintf(
            'Cannot handle "%s" - handler "%s" does not have a handle method.',
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
