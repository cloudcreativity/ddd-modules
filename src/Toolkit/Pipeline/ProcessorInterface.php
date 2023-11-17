<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline;

interface ProcessorInterface
{
    /**
     * Process the payload through the provided stages.
     *
     * @param mixed $payload
     * @param callable ...$stages
     * @return mixed
     */
    public function process(mixed $payload, callable ...$stages): mixed;
}
