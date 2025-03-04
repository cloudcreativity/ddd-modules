<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\InboundEventBus\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Application\InboundEventBus\InboundEventMiddleware;
use CloudCreativity\Modules\Contracts\Toolkit\Loggable\ContextFactory;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\IntegrationEvent;
use CloudCreativity\Modules\Toolkit\Loggable\SimpleContextFactory;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class LogInboundEvent implements InboundEventMiddleware
{
    /**
     * LogInboundEvent constructor.
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
            "Receiving integration event {$name}.",
            $context = ['event' => $this->context->make($event)],
        );

        $next($event);

        $this->log->log($this->publishedLevel, "Received integration event {$name}.", $context);
    }
}
