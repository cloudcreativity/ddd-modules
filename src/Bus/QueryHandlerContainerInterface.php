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

interface QueryHandlerContainerInterface
{
    /**
     * Get a query handler for the provided query name.
     *
     * @param string $queryClass
     * @return QueryHandlerInterface
     */
    public function get(string $queryClass): QueryHandlerInterface;
}