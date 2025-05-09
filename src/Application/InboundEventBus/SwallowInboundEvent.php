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

use CloudCreativity\Modules\Contracts\Toolkit\Messages\IntegrationEvent;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class SwallowInboundEvent
{
    /**
     * SwallowInboundEvent constructor.
     *
     * @param ?LoggerInterface $logger
     * @param string $level
     */
    public function __construct(
        private readonly ?LoggerInterface $logger = null,
        private readonly string $level = LogLevel::DEBUG,
    ) {
    }

    /**
     * Handle the event.
     *
     * @param IntegrationEvent $event
     * @return void
     */
    public function handle(IntegrationEvent $event): void
    {
        $name = ModuleBasename::tryFrom($event)?->toString() ?? $event::class;

        $this->logger?->log(
            $this->level,
            "Swallowing inbound integration event {$name}.",
        );
    }
}
