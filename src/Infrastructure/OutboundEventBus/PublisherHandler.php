<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\OutboundEventBus;

use CloudCreativity\Modules\Contracts\Application\Messages\IntegrationEvent;

final class PublisherHandler implements PublisherHandlerInterface
{
    /**
     * PublisherHandler constructor.
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
        assert(method_exists($this->handler, 'publish'), sprintf(
            'Cannot dispatch "%s" - handler "%s" does not have a publish method.',
            $event::class,
            $this->handler::class,
        ));

        $this->handler->publish($event);
    }
}
