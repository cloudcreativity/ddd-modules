<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Infrastructure\Log;

use Throwable;

interface ExceptionReporterInterface
{
    /**
     * Report the exception.
     *
     * @param Throwable $ex
     * @return void
     */
    public function report(Throwable $ex): void;
}
