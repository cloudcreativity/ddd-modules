<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Bus;

use CloudCreativity\BalancedEvent\Common\Bus\Results\ResultInterface;

interface QueryHandlerInterface extends DispatchThroughMiddleware
{
    /**
     * Execute the query.
     *
     * @param QueryInterface $query
     * @return ResultInterface
     */
    public function __invoke(QueryInterface $query): ResultInterface;
}