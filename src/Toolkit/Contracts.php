<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Toolkit;

final class Contracts
{
    /**
     * Assert that the provided precondition is true.
     *
     * @param bool $precondition
     * @param string $message
     * @return void
     */
    public static function assert(bool $precondition, string $message = ''): void
    {
        if (false === $precondition) {
            throw new ContractException($message);
        }
    }

    /**
     * Contracts constructor.
     */
    private function __construct()
    {
    }
}
