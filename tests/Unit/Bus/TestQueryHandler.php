<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Bus;

use CloudCreativity\BalancedEvent\Common\Bus\DispatchThroughMiddleware;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ResultInterface;

class TestQueryHandler implements TestQueryHandlerInterface, DispatchThroughMiddleware
{
    /**
     * @inheritDoc
     */
    public function execute(TestQuery $query): ResultInterface
    {
        // TODO: Implement execute() method.
    }

    /**
     * @inheritDoc
     */
    public function middleware(): array
    {
        return [];
    }
}
