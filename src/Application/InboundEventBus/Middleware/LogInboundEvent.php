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
use CloudCreativity\Modules\Toolkit\Loggable\ObjectContext;
use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;
use CloudCreativity\Modules\Toolkit\ModuleBasename;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class LogInboundEvent implements InboundEventMiddlewareInterface
{
    /**
     * LogInboundEvent constructor.
     *
     * @param LoggerInterface $log
     * @param string $publishLevel
     * @param string $publishedLevel
     */
    public function __construct(
        private readonly LoggerInterface $log,
        private readonly string $publishLevel = LogLevel::DEBUG,
        private readonly string $publishedLevel = LogLevel::INFO,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(IntegrationEventInterface $event, Closure $next): void
    {
        $name = ModuleBasename::tryFrom($event)?->toString() ?? $event::class;

        $this->log->log(
            $this->publishLevel,
            "Receiving integration event {$name}.",
            $context = ObjectContext::from($event)->context(),
        );

        $next($event);

        $this->log->log($this->publishedLevel, "Received integration event {$name}.", $context);
    }
}