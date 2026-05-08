<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\OutboundEventBus\Middleware;

use Closure;
use CloudCreativity\Modules\Bus\SanitizedMessage;
use CloudCreativity\Modules\Contracts\Bus\Middleware\IntegrationEventMiddleware;
use CloudCreativity\Modules\Contracts\Messaging\IntegrationEvent;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final readonly class LogOutboundEvent implements IntegrationEventMiddleware
{
    public function __construct(
        private LoggerInterface $log,
        private string $publishLevel = LogLevel::DEBUG,
        private string $publishedLevel = LogLevel::INFO,
    ) {
    }

    public function __invoke(IntegrationEvent $event, Closure $next): void
    {
        $name = ModuleBasename::tryFrom($event)?->toString() ?? $event::class;

        $this->log->log(
            $this->publishLevel,
            "Publishing integration event {$name}.",
            $context = ['event' => new SanitizedMessage($event)->context()],
        );

        $next($event);

        $this->log->log($this->publishedLevel, "Published integration event {$name}.", $context);
    }
}
