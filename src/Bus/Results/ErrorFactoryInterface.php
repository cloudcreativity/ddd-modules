<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Bus\Results;

interface ErrorFactoryInterface
{
    /**
     * Ensure the provided value is an error.
     *
     * @param ErrorInterface|string $value
     * @return ErrorInterface
     */
    public function make(ErrorInterface|string $value): ErrorInterface;
}
