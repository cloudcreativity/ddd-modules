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
use Psr\Log\LoggerInterface;
use Throwable;

final class PsrLogExceptionReporter implements ExceptionReporter
{
    /**
     * PsrLogExceptionReporter constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * @inheritDoc
     */
    public function report(Throwable $ex): void
    {
        $this->logger->error(
            $ex->getMessage() ?: 'Unexpected error: ' . $ex::class,
            ['exception' => $ex],
        );
    }
}
