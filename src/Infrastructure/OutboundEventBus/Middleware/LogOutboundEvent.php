<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\OutboundEventBus\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Infrastructure\OutboundEventBus\OutboundEventMiddleware;
use CloudCreativity\Modules\Contracts\Toolkit\Loggable\ContextFactory;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\IntegrationEvent;
use CloudCreativity\Modules\Toolkit\Loggable\SimpleContextFactory;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class LogOutboundEvent implements OutboundEventMiddleware
{
    /**
     * LogOutboundEvent constructor.
     *
     * @param LoggerInterface $log
     * @param string $publishLevel
     * @param string $publishedLevel
     * @param ContextFactory $context
     */
    public function __construct(
        private readonly LoggerInterface $log,
        private readonly string $publishLevel = LogLevel::DEBUG,
        private readonly string $publishedLevel = LogLevel::INFO,
        private readonly ContextFactory $context = new SimpleContextFactory(),
    ) {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(IntegrationEvent $event, Closure $next): void
    {
        $name = ModuleBasename::tryFrom($event)?->toString() ?? $event::class;

        $this->log->log(
            $this->publishLevel,
            "Publishing integration event {$name}.",
            $context = ['event' => $this->context->make($event)],
        );

        $next($event);

        $this->log->log($this->publishedLevel, "Published integration event {$name}.", $context);
    }
}
