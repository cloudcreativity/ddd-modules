<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus;

use CloudCreativity\Modules\Contracts\Bus\EventHandler as IEventHandler;
use CloudCreativity\Modules\Contracts\Messaging\IntegrationEvent;

final readonly class EventHandler implements IEventHandler
{
    use HandlesMessages;

    public function __construct(private object $handler)
    {
    }

    public function __invoke(IntegrationEvent $event): void
    {
        assert(method_exists($this->handler, 'handle'), sprintf(
            'Cannot dispatch "%s" - handler "%s" does not have a handle method.',
            $event::class,
            $this->handler::class,
        ));

        $this->handler->handle($event);
    }
}
