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

interface ErrorIterableFactoryInterface
{
    /**
     * Ensure the provided value is an iterable of errors.
     *
     * @param ErrorIterableInterface|ErrorInterface|iterable|string $errorOrErrors
     * @return ErrorIterableInterface
     */
    public function make(ErrorIterableInterface|ErrorInterface|iterable|string $errorOrErrors): ErrorIterableInterface;
}
