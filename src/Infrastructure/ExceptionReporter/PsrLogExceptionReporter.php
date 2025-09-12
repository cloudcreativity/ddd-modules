<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\ExceptionReporter;

use CloudCreativity\Modules\Contracts\Application\Ports\Driven\ExceptionReporter;
use CloudCreativity\Modules\Contracts\Toolkit\Loggable\ContextProvider;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class PsrLogExceptionReporter implements ExceptionReporter
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function report(Throwable $ex): void
    {
        $this->logger->error(
            $ex->getMessage() ?: 'Unexpected error: ' . $ex::class,
            [...$this->context($ex), 'exception' => $ex],
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    private function context(Throwable $ex): array
    {
        if ($ex instanceof ContextProvider) {
            return $ex->context();
        }

        if (method_exists($ex, 'context')) {
            $context = $ex->context();
            return is_array($context) ? $context : [];
        }

        return [];
    }
}
